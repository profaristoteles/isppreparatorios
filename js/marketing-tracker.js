/**
 * ISP Marketing Tracker - Camada de Rastreamento de Conversões e UTM
 * ISP Preparatórios (v1.0)
 *
 * Centraliza:
 * 1. Captura e persistência de parâmetros UTM (First-touch attribution)
 * 2. Prevenção contra duplicação de eventos (Deduplicação Browser & CAPI)
 * 3. Função de disparo global e segura: window.ispTrackEvent(name, params, options)
 * 4. Tratamento defensivo contra ausência de Pixel/AdBlockers
 */

(function(window, document) {
    'use strict';

    // 1. Inicializar namespaces globais seguros
    window.ispMarketingConfig = window.ispMarketingConfig || {
        pixel_enabled: true,
        debug_mode: false
    };
    window.ispTrackedEvents = window.ispTrackedEvents || new Set();
    window.metaLeadSent = window.metaLeadSent || {};

    const debugLog = function(...args) {
        if (window.ispMarketingConfig && window.ispMarketingConfig.debug_mode) {
            console.info('%c[ISP Tracker]', 'background: #03045e; color: #ff8000; font-weight: bold; padding: 2px 6px; border-radius: 3px;', ...args);
        }
    };

    const debugWarn = function(...args) {
        if (window.ispMarketingConfig && window.ispMarketingConfig.debug_mode) {
            console.warn('%c[ISP Tracker - Aviso]', 'background: #ffc107; color: #000; font-weight: bold; padding: 2px 6px; border-radius: 3px;', ...args);
        }
    };

    // 2. Gerenciamento Inteligente de Parâmetros UTM e Origem de Marketing
    const UTM_STORAGE_KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'isp_landing_page',
        'isp_referrer',
        'isp_first_visit_at'
    ];

    function captureAndPersistMarketingParams() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const now = new Date().toISOString();

            // Se for a primeira visita do usuário nesta sessão, registrar landing page e referrer
            if (!sessionStorage.getItem('isp_landing_page')) {
                sessionStorage.setItem('isp_landing_page', window.location.pathname + window.location.search);
            }
            if (!sessionStorage.getItem('isp_referrer') && document.referrer) {
                sessionStorage.setItem('isp_referrer', document.referrer);
            }
            if (!sessionStorage.getItem('isp_first_visit_at')) {
                sessionStorage.setItem('isp_first_visit_at', now);
            }

            // Capturar parâmetros UTM da URL
            const utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
            const hasNewUtmInUrl = utmKeys.some(key => urlParams.has(key) && urlParams.get(key).trim() !== '');

            // Se houver novos UTMs na URL, atualiza
            if (hasNewUtmInUrl) {
                utmKeys.forEach(key => {
                    const val = urlParams.get(key);
                    if (val !== null && val.trim() !== '') {
                        sessionStorage.setItem(key, val.trim());
                    }
                });
            }

            // Preencher automaticamente todos os inputs de formulários presentes na página
            populateFormMarketingInputs();

        } catch (e) {
            debugWarn('Erro ao capturar parâmetros de marketing:', e);
        }
    }

    function populateFormMarketingInputs() {
        const utmFields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
        
        utmFields.forEach(field => {
            const storedVal = sessionStorage.getItem(field) || '';
            const inputs = document.querySelectorAll(`input[name="${field}"], #${field}`);
            inputs.forEach(input => {
                if (storedVal && !input.value) {
                    input.value = storedVal;
                }
            });
        });

        // Inputs adicionais de landing page e referrer se existirem no formulário
        const landingInput = document.querySelector('input[name="landing_page"], #landing_page');
        if (landingInput && !landingInput.value) {
            landingInput.value = sessionStorage.getItem('isp_landing_page') || window.location.pathname;
        }

        const referrerInput = document.querySelector('input[name="referrer"], #referrer');
        if (referrerInput && !referrerInput.value) {
            referrerInput.value = sessionStorage.getItem('isp_referrer') || document.referrer || '';
        }
    }

    // 3. Função Global de Rastreamento Centralizada: window.ispTrackEvent
    window.ispTrackEvent = function(eventName, parameters, options) {
        parameters = parameters || {};
        options = options || {};

        if (!eventName || typeof eventName !== 'string') {
            debugWarn('Nome do evento inválido fornecido ao ispTrackEvent.');
            return false;
        }

        // 3.1 Verificar se o rastreamento via Pixel está ativo
        if (!window.ispMarketingConfig || !window.ispMarketingConfig.pixel_enabled) {
            debugLog(`Meta Pixel desativado no painel. Evento "${eventName}" não enviado à Meta.`);
            return false;
        }

        // 3.2 Verificar disponibilidade da biblioteca do Meta Pixel (fbq)
        if (typeof window.fbq !== 'function') {
            debugWarn(`Objeto fbq não disponível (pode estar bloqueado por AdBlocker). Evento "${eventName}" não disparado.`);
            return false;
        }

        // 3.3 Deduplicação inteligente de eventos (Browser + CAPI)
        const eventID = options.eventID || null;
        if (eventID) {
            if (window.ispTrackedEvents.has(eventID)) {
                debugWarn(`Deduplicação: evento "${eventName}" com eventID "${eventID}" já foi disparado. Disparo cancelado.`);
                return false;
            }
            window.ispTrackedEvents.add(eventID);
        }

        try {
            const fbqOptions = {};
            if (eventID) {
                fbqOptions.eventID = eventID;
            }

            debugLog(`Disparando evento Meta Pixel: [${eventName}]`, {
                parameters: parameters,
                options: fbqOptions
            });

            // Disparo oficial do Pixel
            if (eventID) {
                window.fbq('track', eventName, parameters, fbqOptions);
            } else {
                window.fbq('track', eventName, parameters);
            }

            return true;
        } catch (err) {
            console.error('[ISP Tracker] Erro ao disparar evento no Meta Pixel:', err);
            return false;
        }
    };

    // 4. Executar captura na inicialização do DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', captureAndPersistMarketingParams);
    } else {
        captureAndPersistMarketingParams();
    }

    // Re-popular inputs dinâmicos caso formulários sejam renderizados via AJAX
    window.ispRepopulateMarketingInputs = populateFormMarketingInputs;

})(window, document);
