/**
 * @file
 * Contains client side logic for dgi_members.
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.compound_members = {
    attach: function (context) {
      once('compound-controller', 'body').forEach(() => {
        Drupal.dgi_members.compound_members.ajaxBegin();
        Drupal.dgi_members.compound_members.appendLabels();
        Drupal.dgi_members.compound_members.updateActiveMetadataDisplay();
      });
      once('compound-controller-metadata', '.object-metadata', context).forEach((element) => {
        Drupal.dgi_members.compound_members.metadataToggleClick(element);
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
     * Construct the metadata toggle elements.
     *
     * @returns {string}
     *   HTML Markup representing the toggle elements.
     */
    toggleSwitch: function () {
        return `<span class='metadata-toggle'>
          <a href='#' class='object-metadata part-metadata'>${Drupal.t("Part")}</a>
          <a href='#' class='object-metadata element-compound'>${Drupal.t("Compound")}</a>
        </span>`;
    },

    /**
     * Attach click handlers to the different metadata displays.
     */
    metadataToggleClick: function (element) {
      $(element).on('click', function (e) {
        e.preventDefault();
        $('.object-metadata').removeClass('element-active');

        let $current = $(e.currentTarget);

        if ($current.hasClass('element-compound')) {
          $('.element-compound').addClass('element-active');
          $(".compound-member-metadata").addClass('hidden');
          $(".compound-object-metadata").removeClass('hidden');
        }
        if ($current.hasClass('part-metadata')) {
          $('.part-metadata').addClass('element-active');
          $(".compound-member-metadata").removeClass('hidden');
          $(".compound-object-metadata").addClass('hidden');
        }
      });
    },

    /**
     * Append the metadata labels to each panel.
     */
    appendLabels: function () {
      $(".compound-object-metadata, .compound-member-metadata").find('.panel-heading').append(
        Drupal.dgi_members.compound_members.toggleSwitch()
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

      $(`.active-node-${drupalSettings.dgi_members.active_nid}`).closest('div.views-row').addClass('active-member');
    }
  };
})(jQuery, Drupal, drupalSettings, once);
