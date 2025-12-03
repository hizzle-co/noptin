<?php

namespace Hizzle\Noptin\Emails\CssToInlineStyles;

use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ExceptionInterface;

class CssToInlineStyles {

	/**
	 * @var CssSelectorConverter
	 */
	private $cssConverter;

	public function __construct() {
		$this->cssConverter = new CssSelectorConverter();
	}

	/**
	 * Will inline the $css into the given $html
	 *
	 * Remark: if the html contains <style>-tags those will be used, the rules
	 * in $css will be appended.
	 *
	 * @param string $html
	 * @param string $css
	 *
	 * @return string
	 */
	public function convert( $html, $css = null ) {
		$document  = $this->createDomDocumentFromHtml( $html );
		$processor = new Css\Processor();

		// get all styles from the style-tags
		$rules = $processor->getRules(
			$processor->getCssFromStyleTags( $html )
		);

		if ( ! is_null( $css ) ) {
			$rules = $processor->getRules( $css, $rules );
		}

		$document = $this->inline( $document, $rules );

		return $this->getHtmlFromDocument( $document );
	}

	/**
	 * Inline the given properties on a given DOMElement
	 *
	 * @param \DOMElement             $element
	 * @param Css\Property\Property[] $properties
	 *
	 * @return \DOMElement
	 */
	public function inlineCssOnElement( \DOMElement $element, array $properties ) {
		if ( empty( $properties ) ) {
			return $element;
		}

		$cssProperties    = array();
		$inlineProperties = array();

		foreach ( $this->getInlineStyles( $element ) as $property ) {
			$inlineProperties[ $property->getName() ] = $property;
		}

		foreach ( $properties as $property ) {
			$inlineProperty = $inlineProperties[ $property->getName() ] ?? null;
			if ( empty( $inlineProperty ) || ( $property->isImportant() && ! $inlineProperty->isImportant() ) ) {
				$cssProperties[ $property->getName() ] = $property;
			}
		}

		$rules = array();
		foreach ( array_merge( $cssProperties, $inlineProperties ) as $property ) {
			$rules[] = $property->toString();
		}
		$element->setAttribute( 'style', implode( ' ', $rules ) );

		return $element;
	}

	/**
	 * Get the current inline styles for a given DOMElement
	 *
	 * @param \DOMElement $element
	 *
	 * @return Css\Property\Property[]
	 */
	public function getInlineStyles( \DOMElement $element ) {
		$processor = new Css\Property\Processor();

		return $processor->convertArrayToObjects(
			$processor->splitIntoSeparateProperties(
				$element->getAttribute( 'style' )
			)
		);
	}

	/**
	 * @param string $html
	 *
	 * @return \DOMDocument
	 */
	protected function createDomDocumentFromHtml( $html ) {
		$document       = new \DOMDocument( '1.0', 'UTF-8' );
		$internalErrors = libxml_use_internal_errors( true );
		$document->loadHTML( mb_encode_numericentity( $html, array( 0x80, 0x10FFFF, 0, 0x1FFFFF ), 'UTF-8' ) );
		libxml_use_internal_errors( $internalErrors );
		$document->formatOutput = true;

		return $document;
	}

	/**
	 * @param \DOMDocument $document
	 *
	 * @return string
	 */
	protected function getHtmlFromDocument( \DOMDocument $document ) {
		// retrieve the document element
		// we do it this way to preserve the utf-8 encoding
		$htmlElement = $document->documentElement;

		if ( is_null( $htmlElement ) ) {
			throw new \RuntimeException( 'Failed to get HTML from empty document.' );
		}

		$html = $document->saveHTML( $htmlElement );

		if ( false === $html ) {
			throw new \RuntimeException( 'Failed to get HTML from document.' );
		}

		$html = trim( $html );

		// retrieve the doctype
		$document->removeChild( $htmlElement );
		$doctype = $document->saveHTML();
		if ( false === $doctype ) {
			$doctype = '';
		}
		$doctype = trim( $doctype );

		// if it is the html5 doctype convert it to lowercase
		if ( '<!DOCTYPE html>' === $doctype ) {
			$doctype = strtolower( $doctype );
		}

		return $doctype . "\n" . $html;
	}

	/**
	 * @param \DOMDocument    $document
	 * @param Css\Rule\Rule[] $rules
	 *
	 * @return \DOMDocument
	 */
	protected function inline( \DOMDocument $document, array $rules ) {
		if ( empty( $rules ) ) {
			return $document;
		}

		// Annotate structural pseudo-classes
		$document = $this->annotateStructuralPseudoClasses( $document );

		/** @var \SplObjectStorage<\DOMElement, array<string, Css\Property\Property>> $propertyStorage */
		$propertyStorage = new \SplObjectStorage();

		$xPath = new \DOMXPath( $document );

		usort( $rules, array( Css\Rule\Processor::class, 'sortOnSpecificity' ) );

		$replacements = array(
			':first-child' => '.noptin__first-child',
			':last-child'  => '.noptin__last-child',
			':only-child'  => '.noptin__only-child',
		);

		foreach ( $rules as $rule ) {
			try {
				$expression = $this->cssConverter->toXPath(
					str_replace(
						array_keys( $replacements ),
						array_values( $replacements ),
						$rule->getSelector()
					)
				);
			} catch ( ExceptionInterface $e ) {
				continue;
			}

			$expression = str_replace( 'descendant-or-self::a[not(position() = last())]', 'descendant-or-self::a[not(position() = last()) and parent::*]', $expression );
			$elements   = $xPath->query( $expression );

			if ( false === $elements ) {
				continue;
			}

			foreach ( $elements as $element ) {

				\assert( $element instanceof \DOMElement );
				$existing_properties         = $propertyStorage->offsetExists( $element ) ? $propertyStorage[ $element ] : array();
				$propertyStorage[ $element ] = $this->calculatePropertiesToBeApplied(
					$rule->getProperties(),
					$existing_properties
				);
			}
		}

		foreach ( $propertyStorage as $element ) {
			$this->inlineCssOnElement( $element, $propertyStorage[ $element ] );
		}

		return $document;
	}

	/**
	 * Merge the CSS rules to determine the applied properties.
	 *
	 * @param Css\Property\Property[] $properties
	 * @param array<string, Css\Property\Property> $cssProperties existing applied properties indexed by name
	 *
	 * @return array<string, Css\Property\Property> updated properties, indexed by name
	 */
	private function calculatePropertiesToBeApplied( array $properties, array $cssProperties ): array {
		if ( empty( $properties ) ) {
			return $cssProperties;
		}

		foreach ( $properties as $property ) {
			if ( isset( $cssProperties[ $property->getName() ] ) ) {
				$existingProperty = $cssProperties[ $property->getName() ];

				//skip check to overrule if existing property is important and current is not
				if ( $existingProperty->isImportant() && ! $property->isImportant() ) {
					continue;
				}

				//overrule if current property is important and existing is not, else check specificity
				$overrule = ! $existingProperty->isImportant() && $property->isImportant();
				if ( ! $overrule ) {
					\assert( $existingProperty->getOriginalSpecificity() !== null, 'Properties created for parsed CSS always have their associated specificity.' );
					\assert( $property->getOriginalSpecificity() !== null, 'Properties created for parsed CSS always have their associated specificity.' );
					$overrule = $existingProperty->getOriginalSpecificity()->compareTo( $property->getOriginalSpecificity() ) <= 0;
				}

				if ( $overrule ) {
					unset( $cssProperties[ $property->getName() ] );
					$cssProperties[ $property->getName() ] = $property;
				}
			} else {
				$cssProperties[ $property->getName() ] = $property;
			}
		}

		return $cssProperties;
	}

	private function annotateStructuralPseudoClasses( \DOMDocument $doc ): \DOMDocument {
		$xpath = new \DOMXPath( $doc );

		try {
			// Select all elements
			foreach ( $xpath->query( '//*' ) as $element ) {
				if ( ! $element instanceof \DOMElement ) {
					continue;
				}

				$parent = $element->parentNode;
				if ( ! $parent instanceof \DOMElement ) {
					continue;
				}

				// Get element-siblings only
				$siblings = array();
				foreach ( $parent->childNodes as $node ) {
					if ( $node instanceof \DOMElement ) {
						$siblings[] = $node;
					}
				}

				$count = count( $siblings );

				if ( 1 === $count ) {
					$this->addClass( $element, 'noptin__only-child' );
					$this->addClass( $element, 'noptin__first-child' );
					$this->addClass( $element, 'noptin__last-child' );
					continue;
				}

				// First child
				if ( $siblings[0] === $element ) {
					$this->addClass( $element, 'noptin__first-child' );
				}

				// Last child
				if ( $siblings[ $count - 1 ] === $element ) {
					$this->addClass( $element, 'noptin__last-child' );
				}
			}
		} catch ( \Exception $e ) {
			// Fail silently
		}

		return $doc;
	}

	private function addClass( \DOMElement $el, string $css_class ): void {
		$existing = $el->getAttribute( 'class' );
		$classes  = preg_split( '/\s+/', trim( $existing ) );
		$classes  = is_array( $classes ) ? $classes : array();
		if ( ! in_array( $css_class, $classes, true ) ) {
			$classes[] = $css_class;
			$el->setAttribute( 'class', trim( implode( ' ', $classes ) ) );
		}
	}
}
