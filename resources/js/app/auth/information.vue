<template>
    <div>

        <form id="data_form" enctype="multipart/form-data">

            <div class="text-center">
                <span class="badge badge-outline text-default">Problemas al iniciar sesión</span>
            </div>
            <br>

            <div class="alert alert-minor alert-info alert-dismissible" role="alert">
                <div class="alert-icon">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/info-circle -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon alert-icon icon-2">
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                        <path d="M12 9h.01"></path>
                        <path d="M11 12h1v4h1"></path>
                    </svg>
                </div>
                <div>
                    <div class="alert-description" style="text-align: justify;">
                        Envía un correo electrónico a <strong>soporte_rh@imssbienestar.gob.mx</strong> para obtener la
                        información que deseas.
                    </div>
                </div>
            </div>

            <!--
            <div class="card">
                <div class="accordion accordion-flush" id="accordion-flush">
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-1-flush" aria-expanded="false">
                                ¿No me llegó ningún correo electrónico?
                                <div class="accordion-button-toggle">
            
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="icon icon-1">
                                        <path d="M6 9l6 6l6 -6"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                        <div id="collapse-1-flush" class="accordion-collapse collapse" data-bs-parent="#accordion-flush"
                            style="">
                            <div class="accordion-body">
                                Verifica tu carpeta de spam o no deseados.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-2-flush" aria-expanded="false">
                                ¿Mi RFC no aparece registrado en la aplicación?
                                <div class="accordion-button-toggle">
               
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="icon icon-1">
                                        <path d="M6 9l6 6l6 -6"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                        <div id="collapse-2-flush" class="accordion-collapse collapse" data-bs-parent="#accordion-flush"
                            style="">
                            <div class="accordion-body">
                                Verifica que cumples todas las métricas; si es asi envía un correo con
                                los documentos que lo respalden.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-3-flush" aria-expanded="true">
                                ¿No puedo crear mi usuario porque el tiempo ha caducado?
                                <div class="accordion-button-toggle">
                
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="icon icon-1">
                                        <path d="M6 9l6 6l6 -6"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                        <div id="collapse-3-flush" class="accordion-collapse collapse show"
                            data-bs-parent="#accordion-flush" style="">
                            <div class="accordion-body">
                                Existe un plazo para crear usuarios, asegúrate de registrarte dentro de él.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
-->

        </form>

        <div class="text-center text-secondary mt-3">
            <a :href="`${BASE_URL}/login`" tabindex="-1">Regresar</a>
        </div>

    </div>
</template>

<script setup>
import { BASE_URL } from '@/components/url.js';

</script>
