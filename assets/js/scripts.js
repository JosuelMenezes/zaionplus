// Controle do menu lateral em dispositivos móveis
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    
    // Função para mostrar o menu
    function showSidebar() {
        sidebar.classList.add('show');
        sidebarBackdrop.classList.add('show');
        document.body.style.overflow = 'hidden'; // Impedir rolagem
    }
    
    // Função para esconder o menu
    function hideSidebar() {
        sidebar.classList.remove('show');
        sidebarBackdrop.classList.remove('show');
        document.body.style.overflow = ''; // Restaurar rolagem
    }
    
    // Evento de clique no botão do menu
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (sidebar.classList.contains('show')) {
                hideSidebar();
            } else {
                showSidebar();
            }
        });
    }
    
    // Fechar menu ao clicar no backdrop
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', hideSidebar);
    }
    
    // Fechar menu ao clicar em um link (em dispositivos móveis)
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                hideSidebar();
            }
        });
    });
    
    // Fechar menu ao redimensionar para desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768 && sidebar.classList.contains('show')) {
            hideSidebar();
        }
    });
    
 // Registrar Service Worker para PWA
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            const basePath = document.body.getAttribute('data-base-path') || '';
            navigator.serviceWorker.register(basePath + 'sw.js')
                .then(registration => {
                    console.log('Service Worker registrado com sucesso:', registration.scope);
                })
                .catch(error => {
                    console.log('Falha ao registrar o Service Worker:', error);
                });
        });
    }
    
    // Função para formatar valores monetários
    function formatMoney(value) {
        if (typeof value === 'string') {
            value = parseFloat(value.replace(',', '.'));
        }
        return value.toFixed(2).replace('.', ',');
    }
    
    // Função para gerar cor baseada em string
    function stringToColor(str) {
        if (!str) return '#6c757d';
        
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        
        let color = '#';
        for (let i = 0; i < 3; i++) {
            const value = (hash >> (i * 8)) & 0xFF;
            color += ('00' + value.toString(16)).substr(-2);
        }
        
        return color;
    }
    
    // Inicializar tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Adicionar classe 'active' ao link atual no menu
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href) && href !== '/') {
                link.classList.add('active');
            }
        });
    });
    
    // Função para mostrar mensagens de toast
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            const newContainer = document.createElement('div');
            newContainer.id = 'toast-container';
            newContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(newContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-${type} text-white">
                    <strong class="me-auto">${type === 'success' ? 'Sucesso' : type === 'danger' ? 'Erro' : 'Informação'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Fechar"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        const container = document.getElementById('toast-container');
        container.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        
        // Remover o toast do DOM após ser ocultado
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }
});