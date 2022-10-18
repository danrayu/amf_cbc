import Vue from 'vue';
import App from './App.vue';

// Enable helper.
export var Helper = new Vue({
  data: {
    Drupal: null,
    settings: null
  },

  methods: {}
});
(function (Drupal, drupalSettings) {
  Helper.settings = drupalSettings;
  Helper.Drupal = Drupal;
})(Drupal, drupalSettings);

// Create a root instance
const vueElement = document.getElementById('amf-cbc-app');
if (typeof vueElement === 'object') {

  /**
   * Send xhr to the server.
   *
   * @param params
   *   Parameters to be passed with the xhr.
   * @param callback
   *   Callback.
   */
  Vue.prototype.xhr = function (params, callback) {
    var uri = '/amf_cbc/xhr';
    var xhr = new XMLHttpRequest();
    var pack = new FormData();
    var token = drupalSettings.amf_cbc.token || '';
    for (var i in params) {
      pack.append(i, params[i]);
    }
    xhr.onreadystatechange = function () {
      if (this.readyState === 4) {
        if (this.status === 200) {
          var data = JSON.parse(xhr.responseText);
          if (typeof data.error === 'undefined') {
            callback({request: params, data: data});
          }
          else {
            callback({
              request: params, data: {
                'error': true,
                'message': 'Error: ' + data.error
              }
            });
          }
        }
        else {
          callback({
            request: params, data: {
              'error': true,
              'message': 'Error ' + this.status + ', "' + this.statusText + '"'
            }
          });
        }
      }
    };
    if (token) {
      xhr.open('POST', uri + '?token=' + token);
    }
    else {
      xhr.open('POST', uri);
    }
    xhr.send(pack);
  };

  /**
   * Disable the "Next Page" button.
   */
  Vue.prototype.disableNext = function () {
    jQuery('.webform-submission-financial-app-form input.webform-button--next').attr('disabled', true).css('visibility', 'hidden');
  };

  /**
   * Enable the "Next Page" button.
   */
  Vue.prototype.enableNext = function () {
    jQuery('.webform-submission-financial-app-form input.webform-button--next').attr('disabled', false).css('visibility', 'visible');
  };

  /**
   * Get the data from the form hidden input.
   */
  Vue.prototype.getData = function () {
    var $elm = jQuery('.webform-submission-financial-app-form input.calculator-data');
    var value = $elm.val();
    try {
      return JSON.parse(value);
    } catch (error) {
      return {};
    }
  };

  /**
   * Set the data into the form hidden input.
   */
  Vue.prototype.setData = function (data) {
    var $elm = jQuery('.webform-submission-financial-app-form input.calculator-data');
    $elm.val(JSON.stringify(data));
  };

  /**
   * Round to cents.
   */
  Vue.prototype.round = function (number) {
    return number.toFixed(2);
  };

  new Vue({
    el: vueElement,
    data: {
      Helper
    },
    render: h => h(App)
  });
}
