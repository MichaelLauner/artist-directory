(function (blocks, blockEditor, components, element, i18n) {
	const el = element.createElement;
	const __ = i18n.__;
	const useBlockProps = blockEditor.useBlockProps;

	blocks.registerBlockType('artist-directory/directory', {
		edit: function () {
			const blockProps = useBlockProps({
				className: 'artist-directory-block-editor',
			});

			return el(
				'div',
				blockProps,
				el(
					components.Placeholder,
					{
						icon: 'groups',
						label: __('Artist Directory', 'artist-directory'),
					},
					el(
						'p',
						null,
						__('Displays the artist directory on the front end. Use the block toolbar to set wide or full width.', 'artist-directory')
					)
				)
			);
		},
		save: function () {
			return null;
		},
	});
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n);
