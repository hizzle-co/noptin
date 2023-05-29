/**
 * External dependencies
 */
import { plus, cloudUpload, download, trash } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";
import { compact } from "lodash";
import {
	Flex,
	FlexItem,
	FlexBlock,
	Card,
	CardHeader,
	Button,
	__experimentalText as Text,
	__experimentalUseNavigator as useNavigator,
} from "@wordpress/components";
import { useAtom, useAtomValue, useSetAtom } from "jotai";

import AppIcon from "./app-icon";
import { route, namespace, collection, components } from './store';

/**
 * Displays the collection navigation.
 *
 * @returns {JSX.Element} Table actions.
 */
export default function Navigation() {

	const { goTo } = useNavigator();
	const allComponents = useAtomValue( components );
	const setNamespace = useSetAtom( namespace );
	const setCollection = useSetAtom( collection );
	const [ currentPath, setCurrentPath ] = useAtom( route );

	// Filter out components that don't have a display.
	const toDisplay = compact( Object.keys( allComponents ).map( ( component ) => {

		// Don't display components that don't have a display.
		if ( allComponents[component].hide ) {
			return null;
		}

		const newComponent = { ...allComponents[component], key: component };

		// Add icon if it doesn't exist.
		if ( ! newComponent.icon ) {

			switch (newComponent.component) {
				case 'create-record':
					newComponent.icon = plus;
					break;
				case 'import':
					newComponent.icon = cloudUpload;
					break;
				case 'export':
					newComponent.icon = download;
					break;
				case 'delete':
					newComponent.icon = trash;
					break;
			}
		}

		return newComponent;
	} ) );

	// Set the current path.
	const navigateTo = ( path ) => {

		// Navigate to the path.
		setCurrentPath( { path } );
		goTo( path );

		// Maybe set the namespace and collection.
		if ( allComponents[path].namespace ) {
			setNamespace( allComponents[path].namespace );
		}

		if ( allComponents[path].collection ) {
			setCollection( allComponents[path].collection );
		}
	};

	return (
		<Card>
			<CardHeader>
				<Flex wrap>

					<FlexBlock>
						<Flex justify="start" wrap>
							<FlexItem>
								<AppIcon />
							</FlexItem>
							<FlexItem>
								<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
									Noptin
								</Text>
							</FlexItem>
						</Flex>
					</FlexBlock>

					{ toDisplay.map( ( component ) => {
						return (
							<FlexItem key={ component.key }>
								<Button
									onClick={ () => navigateTo( component.key ) }
									isPressed={ currentPath.path === component.key }
									icon={ component.icon }
									text={ component.title }
									id={`noptin-collection-navigation__button-${ component.key }`}
									__experimentalIsFocusable
								/>
							</FlexItem>
						);
					})}

				</Flex>
			</CardHeader>
		</Card>
	);
};
