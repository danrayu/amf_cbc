<template>
  <div id="amf_cbc_app" class="vuejs">
    <div class="app-container">
      <div class="app-content">
        <form class="cbc-form" v-if="status !== 'success'">

          <div class="message info">
            User information in this step is for the credit system, we don't store it.
          </div>

          <h3>Employer Information</h3>
          <fieldset class="form-item form-group">
            <label for="edit-length-employment" class="form-required">Company Name</label>
            <input v-model="values.company_name" type="text" id="edit-employer-name" class="form-text form-control" required="required">
          </fieldset>
          <fieldset class="form-item form-group">
            <label for="edit-occupation" class="form-required">Occupation</label>
            <input v-model="values.occupation" type="text" id="edit-occupation" class="form-text form-control" required="required">
          </fieldset>

          <h3>Identity Information</h3>
          <fieldset class="form-item form-group">
            <label for="edit-ssn" class="form-required">Social Security Number</label>
            <input v-model="values.ssn" type="text" id="edit-ssn" class="form-text form-control" required="required">
          </fieldset>

          <h3>Housing Status</h3>
          <fieldset class="form-item form-group">
            <label for="edit-housing_status" class="form-required">Housing Status</label>
            <input v-model="values.housing_status" type="text" id="edit-housing_status" class="form-text form-control" required="required">
          </fieldset>
          <fieldset class="form-item form-group">
            <label for="edit-period_residence" class="form-required">How Long (months)?</label>
            <input v-model="values.period_residence" type="text" id="edit-period_residence" class="form-text form-control" required="required">
          </fieldset>

          <div v-if="status === 'loading'" class="spinner small"></div>
          <div v-if="status === 'fail'" class="message error">
            Credit application could not be submitted. Could be a connectivity problem.<br />Please try again after a few minutes.
          </div>
          <div v-if="status === 'incomplete'" class="message error">
            Please fill in all the required fields.
          </div>

          <fieldset v-if="status === 'new' || status === 'fail' || status === 'incomplete'" class="form-item form-group">
            <a href="#" @click.prevent="submit" class="btn btn-secondary">Submit</a>
          </fieldset>

        </form>

        <div v-if="status === 'success'" class="message ok">
          Credit information has been submitted. Please continue with the application.
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {Helper} from './main.js';
export default {
  name: 'amf_cbc_app',
  data () {
    return {
      Helper,
      t: Helper.Drupal.t,
      status: 'new',
      data: {},
      values: {
        'submission_data': Helper.settings.amf_cbc.submission_data || ''
      },
    }
  },
  created () {
    this.disableNext();
  },
  methods: {
    submit: function () {
      if (!this.validate()) {
        return;
      }
      this.status = 'loading';
      let params = {
        action: 'submit',
        values: JSON.stringify(this.values)
      };
      this.xhr(params, (data) => {
        data = this.checkXhr(data);
        data.data = data.data || 0;
        if (data.data === 1) {
          this.status = 'success';
          this.enableNext();
        }
        else {
          this.status = 'fail';
        }
      });
    },
    validate: function () {
      var expected = 3;
      var count = 0;
      for (const value in this.values) {
        if (value.length < 1) {
          this.status = 'incomplete';
          return false;
        }
        count++;
      }
      if (count < expected) {
        this.status = 'incomplete';
        return false;
      }

      return true;
    },
    checkXhr: function (data) {
      return data;
    }
  }

}
</script>

<style lang="scss">
#amf_cbc_app {
  text-align: left;
  margin: 2em 0;
  max-width: 640px;
  .app-container {
    padding: 2em;
    background: #fafafa;
  }
  .app-content {
    margin: 1em 0;
  }
}
</style>
