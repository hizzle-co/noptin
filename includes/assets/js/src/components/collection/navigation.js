/**
 * External dependencies
 */
import { plus, cloudUpload, download, trash } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";
import {
	Flex,
	FlexItem,
	FlexBlock,
	Card,
	CardHeader,
	Button,
	__experimentalText as Text,
} from "@wordpress/components";

import AppIcon from "./app-icon";
import { useRoute } from "./hooks";
import { useSchema } from "../../store-data/hooks";

/**
 * Displays the collection navigation.
 *
 * @returns {JSX.Element} Table actions.
 */
export default function Navigation() {

	const { namespace, collection, path, navigate } = useRoute();
	const { data } = useSchema( namespace, collection );

	// Filter out routes that don't have a display.
	const TheRoutes = Object.keys( data.routes ).map( ( route ) => {

		// Don't display routes that don't have a display.
		if ( data.routes[ route ].hide ) {
			return null;
		}

		// Prepare the icon.
		let icon = data.routes[ route ].icon;

		// Add icon if it doesn't exist.
		if ( ! icon ) {

			switch (data.routes[ route ].component) {
				case 'create-record':
					icon = plus;
					break;
				case 'import':
					icon = cloudUpload;
					break;
				case 'export':
					icon = download;
					break;
				case 'delete':
					icon = trash;
					break;
			}
		}

		return (
			<FlexItem key={ route }>
				<Button
					onClick={ () => navigate( route ) }
					isPressed={ path === route }
					icon={ icon }
					text={ data.routes[ route ].title }
					id={`noptin-collection-navigation__button-${ route }`}
				/>
			</FlexItem>
		);

	} );

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

					{ TheRoutes }

				</Flex>
			</CardHeader>
		</Card>
	);
};
