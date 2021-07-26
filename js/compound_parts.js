/**
 * @file
 * Contains client side logic for dgi_members.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.compound_members = {
    attach: function (context, settings) {
      $(document).once('compound_controller_ajax_begin').each(this.ajaxBegin);
      $(document).once('compound_controller_ajax_complete').each(this.ajaxComplete);
      $(document).once('compound_controller_add_labels').each(this.appendLabels);
      $(".object-metadata").once('compound_controller_click_callback').each(this.metadataToggleClick);
      $(document).once('compound_controller_update_active').each(this.updateActiveMetadataDisplay());
    },

    /**
     * Toggle on or off the ajax spinner.
     */
    toggleSpinner: function () {
      var toggle_element = ".multi-object-navigation header i";
      $(toggle_element).toggleClass('visually-hidden');
      $(".multi-object-navigation .view-content.solr-search-row-content").toggleClass('ajax-active');
    },

    /**
     * Attach event to ajaxSend to toggle the ajax spinner.
     */
    ajaxBegin: function () {
      $(document).ajaxSend(function (event, xhr, settings) {
        if (settings.url.startsWith("/views/ajax?")) {
          Drupal.behaviors.compound_members.toggleSpinner();
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
        return "<span class='metadata-toggle'><a href='#' class='object-metadata part-metadata'>" + Drupal.t("Part") + "</a><a href='#' class='object-metadata element-compound'>" + Drupal.t("Compound") + "</a></span>"
    },

    /**
     * Attach click handlers to the different metadata displays.
     */
    metadataToggleClick: function () {
      $(this).on('click', function (e) {
        e.preventDefault();
        $('.object-metadata').removeClass('element-active');

        if ($(e.currentTarget).hasClass('element-compound')) {
          $('.element-compound').addClass('element-active');
          $(".compound-member-metadata").addClass('hidden');
          $(".compound-object-metadata").removeClass('hidden');
        }
        if ($(e.currentTarget).hasClass('part-metadata')) {
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
      $(".compound-object-metadata").find('.panel-heading').append(
        Drupal.behaviors.compound_members.toggleSwitch()
      );

      $(".compound-member-metadata").find('.panel-heading').append(
        Drupal.behaviors.compound_members.toggleSwitch()
      );
    },

    /**
     * Update the active member, displaying the proper metadata for the active object.
     *
     * This will also update the '.active-node-{ID}' where it is found on the page,
     * including in the compound navigator block and the 'Parts' sidebar block.
     */
    updateActiveMetadataDisplay: function () {
      if (drupalSettings.dgi_members.has_members) {
        $(".compound-object-metadata").addClass('hidden');
        $('.object-metadata.element-compound').removeClass('element-active');
        $('.object-metadata.part-metadata').addClass('element-active');
      }
      else {
        $('.object-metadata.element-compound').addClass('element-active');
        $('.object-metadata.part-metadata').removeClass('element-active');
      }

      $(".active-node-" + drupalSettings.dgi_members.active_nid).closest('div.views-row').addClass('active-member');
    }
  };
})(jQuery, Drupal, drupalSettings);
