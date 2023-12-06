/**
 * @file
 * Contains client side logic for dgi_members.
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.dgi_members_compound_parts = {
    attach: function (context) {
      once('dgi_members-compound_controller', 'body').forEach(() => {
        Drupal.dgi_members.compound_members.ajaxBegin();
        Drupal.dgi_members.compound_members.appendLabels();
        Drupal.dgi_members.compound_members.updateActiveMetadataDisplay();
      });

      function _click(e, active_selector, hide_selector, show_selector) {
        e.preventDefault();
        $('.object-metadata').removeClass('element-active');

        $(active_selector).addClass('element-active');
        $(hide_selector).addClass('hidden')
        $(show_selector).removeClass('hidden');
      }

      once('dgi_members-compound_controller_metadata-element', '.object-metadata.element-compound', context).forEach((element) => {
        $(element).on('click', function (e) {
          _click(
            e,
            '.object-metadata.element-compound',
            ".compound-member-metadata",
            ".compound-object-metadata",
          );
        });
      });
      once('dgi_members-compound_controller_metadata-part', '.object-metadata.part-metadata', context).forEach((element) => {
        $(element).on('click', function (e) {
          _click(
            e,
            '.object-metadata.part-metadata',
            ".compound-object-metadata",
            ".compound-member-metadata",
          );
        });
      });
    }
  };

  Drupal.dgi_members = Drupal.dgi_members || {};
  Drupal.dgi_members.compound_members = {
    /**
     * Toggle on or off the ajax spinner.
     */
    toggleSpinner: function () {
      let $nav = $('.multi-object-navigation');
      $nav.find("header i").toggleClass('visually-hidden');
      $nav.find(".view-content.solr-search-row-content").toggleClass('ajax-active');
    },

    /**
     * Attach event to ajaxSend to toggle the ajax spinner.
     */
    ajaxBegin: function () {
      $(document).ajaxSend(function (event, xhr, settings) {
        if (settings.url.startsWith("/views/ajax?")) {
          Drupal.dgi_members.compound_members.toggleSpinner();
        }
      });
    },

    /**
     * Append the metadata labels to each panel.
     */
    appendLabels: function () {
      $(".compound-object-metadata, .compound-member-metadata").find('.panel-heading').append(
        // XXX: Drupal's/Squiz coding standards do not presently appears to be
        // aware of the possibility of template strings in Javascript.
        // phpcs:disable Squiz.WhiteSpace.OperatorSpacing.NoSpaceBefore,Squiz.WhiteSpace.OperatorSpacing.NoSpaceAfter
        `<span class='metadata-toggle'>
          <a href='#' class='object-metadata part-metadata'>${Drupal.t("Part")}</a>
          <a href='#' class='object-metadata element-compound'>${Drupal.t("Compound")}</a>
        </span>`
        // phpcs:enable Squiz.WhiteSpace.OperatorSpacing.NoSpaceBefore,Squiz.WhiteSpace.OperatorSpacing.NoSpaceAfter
      );
    },

    /**
     * Update the active member, displaying the proper metadata for the active object.
     *
     * This will also update the '.active-node-{ID}' where it is found on the page,
     * including in the compound navigator block and the 'Parts' sidebar block.
     */
    updateActiveMetadataDisplay: function () {
      const $ec = $('.object-metadata.element-compound');
      const $pm = $('.object-metadata.part-metadata');
      const ac = 'element-active';

      if (drupalSettings.dgi_members.has_members) {
        $(".compound-object-metadata").addClass('hidden');
        $ec.removeClass(ac);
        $pm.addClass(ac);
      }
      else {
        $ec.addClass(ac);
        $pm.removeClass(ac);
      }

      // XXX: Drupal's/Squiz coding standards do not presently appears to be
      // aware of the possibility of template strings in Javascript.
      // phpcs:ignore Squiz.WhiteSpace.OperatorSpacing.NoSpaceBefore,Squiz.WhiteSpace.OperatorSpacing.NoSpaceAfter
      $(`.active-node-${drupalSettings.dgi_members.active_nid}`).closest('div.views-row').addClass('active-member');
    }
  };
})(jQuery, Drupal, drupalSettings, once);
