/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './edit.css';

export const name = 'frego-mobile-builder/hello-world';
export const Edit = () => <h2>{ __( 'Hello Editor', 'frego-mobile-builder' ) }</h2>;
export const Save = () => <h2>{ __( 'Hello Website', 'frego-mobile-builder' ) }</h2>;

export const settings = {
	title: __( 'Hello World', 'frego-mobile-builder' ),
	icon: 'smiley',
	category: 'common',
	edit: Edit,
	save: Save,
};
