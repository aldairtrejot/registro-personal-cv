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
import vue_form_create from './app/auth/create.vue'
import vue_form_recover from './app/auth/recover.vue'
import vue_form_information from './app/auth/information.vue'

// --- Administration: User / Role ---
import vue_user_list from './app/administration/user/list.vue'
import vue_user_form from './app/administration/user/form.vue'
import vue_role_list from './app/administration/role/list.vue'
import vue_entity_list from './app/administration/entity/list.vue'
import vue_entity_form from './app/administration/entity/form.vue'
import vue_useremployee_list from './app/administration/useremployee/list.vue'

// --- Administration: Zone ---
import vue_zone_list from './app/administration/zone/list.vue'
import vue_zone_form from './app/administration/zone/form.vue'

// --- Administration: Employee ---
import vue_employee_list from './app/administration/employee/list.vue'
import vue_employee_form from './app/administration/employee/form.vue'
import vue_employee_modal_history from './app/administration/employee/followHistoryEmployee.vue'
import vue_doc_history_modal from './app/administration/employee/docHistoryModal.vue'

// --- Administration: Branch ---
import vue_Branch_list from './app/administration/Branch/list.vue'
import vue_Branch_form from './app/administration/Branch/form.vue'

// --- Follow ---
import vue_form_update_follow from './app/follow/followUpdate.vue'
import vue_follow_dashboard from './app/follow/follow.vue'
import vue_modal_follow_history from './app/follow/followHistory.vue'
import vue_data_employee from './app/follow/data.vue'

// --- CV ---
import RegistroWizard from './app/registro/RegistroWizard.vue'
import RevisorEmpleadoList from './app/revisor/RevisorEmpleadoList.vue'
import RevisorEmpleadoShow from './app/revisor/RevisorEmpleadoShow.vue'

// Lista de componentes a montar (selector -> componente)
const components = [
  // Auth
  { selector: '#blade_form_login', component: vue_form_login },
  { selector: '#blade_form_create', component: vue_form_create },
  { selector: '#blade_form_recover', component: vue_form_recover },
  { selector: '#blade_logout', component: vue_logout },
  { selector: '#blade_form_information', component: vue_form_information },

  // User / Role
  { selector: '#blade_user_list', component: vue_user_list },
  { selector: '#blade_user_form', component: vue_user_form },
  { selector: '#blade_role_list', component: vue_role_list },

  // Entity
  { selector: '#blade_entity_list', component: vue_entity_list },
  { selector: '#blade_entity_form', component: vue_entity_form },

  // User-Employee
  { selector: '#blade_useremployee_list', component: vue_useremployee_list },

  // Zone
  { selector: '#blade_zone_list', component: vue_zone_list },
  { selector: '#blade_zone_form', component: vue_zone_form },

  // Follow
  { selector: '#blade_form_update_follow', component: vue_form_update_follow },
  { selector: '#blade_follow_dashboard', component: vue_follow_dashboard },
  { selector: '#blade_modal_follow_history', component: vue_modal_follow_history },
  { selector: '#blade_data_employee', component: vue_data_employee },

  // Employee
  { selector: '#blade_employee_list', component: vue_employee_list },
  { selector: '#blade_employee_form', component: vue_employee_form },
  { selector: '#blade_employee_modal_history', component: vue_employee_modal_history },
  { selector: '#blade_doc_history_modal', component: vue_doc_history_modal },

  // Branch
  { selector: '#blade_Branch_list', component: vue_Branch_list },
  { selector: '#blade_Branch_form', component: vue_Branch_form },

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
    if (selector === '#blade_follow_dashboard' && window.FOLLOW_PROPS) props = window.FOLLOW_PROPS
    if (selector === '#blade_form_update_follow' && window.FOLLOW_UPDATE_PROPS) props = window.FOLLOW_UPDATE_PROPS

    const app = createApp(component, props)
    app.mount(el)
    el.__vue_app__ = app
  })
})