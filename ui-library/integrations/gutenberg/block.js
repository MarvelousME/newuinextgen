(function (wp) {
  'use strict';
  if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components || !wp.serverSideRender) {
    return;
  }

  var registerBlockType = wp.blocks.registerBlockType;
  var createElement = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var PanelBody = wp.components.PanelBody;
  var SelectControl = wp.components.SelectControl;
  var TextControl = wp.components.TextControl;
  var TextareaControl = wp.components.TextareaControl;
  var ServerSideRender = wp.serverSideRender;

  var options = (window.ngtUiBlockData && window.ngtUiBlockData.components) || [
    { value: 'magic-card', label: 'Magic Card' }
  ];

  registerBlockType('ngt-ui/component', {
    edit: function (props) {
      var attrs = props.attributes;
      var setAttributes = props.setAttributes;
      var blockProps = useBlockProps({ className: 'ngt-ui-block-editor' });

      return createElement(
        Fragment,
        null,
        createElement(
          InspectorControls,
          null,
          createElement(
            PanelBody,
            { title: 'NGT UI Component', initialOpen: true },
            createElement(SelectControl, {
              label: 'Component',
              value: attrs.component || 'magic-card',
              options: options,
              onChange: function (v) {
                setAttributes({ component: v });
              }
            }),
            createElement(TextControl, {
              label: 'Title / text',
              value: attrs.title || attrs.text || '',
              onChange: function (v) {
                setAttributes({ title: v, text: v });
              }
            }),
            createElement(TextControl, {
              label: 'Button label',
              value: attrs.label || '',
              onChange: function (v) {
                setAttributes({ label: v });
              }
            }),
            createElement(TextControl, {
              label: 'Items (pipe-separated)',
              value: attrs.items || '',
              onChange: function (v) {
                setAttributes({ items: v });
              }
            }),
            createElement(TextareaControl, {
              label: 'Content',
              value: attrs.content || '',
              onChange: function (v) {
                setAttributes({ content: v });
              }
            }),
            createElement(TextareaControl, {
              label: 'Extra settings (JSON)',
              value: attrs.settingsJson || '{}',
              onChange: function (v) {
                setAttributes({ settingsJson: v });
              }
            })
          )
        ),
        createElement(
          'div',
          blockProps,
          createElement(ServerSideRender, {
            block: 'ngt-ui/component',
            attributes: attrs
          })
        )
      );
    },
    save: function () {
      return null;
    }
  });
})(window.wp);
