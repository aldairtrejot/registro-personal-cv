<template>
  <modalTemplate
    modal-id="modal_logout_vue"
    :on-confirm="send_form"
    dialog-class="modal-dialog modal-dialog-centered modal-sm"
  >
    <h3>¿Está seguro que desea cerrar la sesión?</h3>
  </modalTemplate>
</template>

<script setup>
import { notyf } from '@components/notyf.js';
import { BASE_URL } from '@/components/url.js';
import axios from '@axios';
import modalTemplate from '@helpers/modal/modal-template.vue';
import { showSpinner, hideSpinner } from '@components/spinner.js';

function hideModal(modalId) {
  const el = document.getElementById(modalId);
  if (!el) return;

  // Bootstrap 5
  if (window.bootstrap?.Modal) {
    const instance = window.bootstrap.Modal.getInstance(el);
    if (instance) instance.hide();
    return;
  }

  // Bootstrap 4
  if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
    window.jQuery(el).modal('hide');
  }
}

async function send_form() {
  try {
    hideModal('modal_logout_vue');
    showSpinner();

    const result = await axios.post('/logout', null, { headers: { Accept: 'application/json' } });

    if (result?.data?.status) {
      window.location.href = `${BASE_URL}/login`;
    } else {
      notyf.error(result?.data?.message || 'No se pudo cerrar la sesión.');
    }
  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.');
  } finally {
    hideSpinner();
  }
}
</script>