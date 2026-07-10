// resources/js/app.js
import * as bootstrap from 'bootstrap'
window.bootstrap = bootstrap

import { createApp } from 'vue'
import axiosInstance from '@axios'

// Exponer axios instance global (por compatibilidad con código viejo)
window.axios = axiosInstance

// --- Auth ---
import vue_form_login from './app/auth/login.vue'
import vue_logout from './app/auth/logout.vue'

// --- CV ---
import RegistroWizard from './app/registro/RegistroWizard.vue'
import RevisorEmpleadoList from './app/revisor/RevisorEmpleadoList.vue'
import RevisorEmpleadoShow from './app/revisor/RevisorEmpleadoShow.vue'

// Lista de componentes a montar (selector -> componente)
const components = [
  // Auth
  { selector: '#blade_form_login', component: vue_form_login },
  { selector: '#blade_logout', component: vue_logout },

  // CV
  { selector: '#blade_registro_wizard', component: RegistroWizard },
  { selector: '#blade_revisor_empleados', component: RevisorEmpleadoList },
  { selector: '#blade_revisor_empleado_show', component: RevisorEmpleadoShow },
]

// Monta cada componente si existe su contenedor en el DOM
document.addEventListener('DOMContentLoaded', () => {
  components.forEach(({ selector, component }) => {
    const el = document.querySelector(selector)
    if (!el || el.__vue_app__) return

    // Inyecta props desde Blade para los módulos de Follow
    let props = {}

    const app = createApp(component, props)
    app.mount(el)
    el.__vue_app__ = app
  })
})
