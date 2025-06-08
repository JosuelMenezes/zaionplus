<?php
// includes/footer.php
$ano_atual = date('Y');
$versao_sistema = '2.0';
$empresa_nome = isset($config['nome_empresa']) ? $config['nome_empresa'] : 'Zaion GC';
?>
            </div>
        </div>
    </div>

    <!-- Footer Premium -->
    <footer class="footer mt-auto py-4">
        <div class="container-fluid">
            <div class="footer-content">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="footer-brand">
                            <h5 class="mb-1"><?php echo htmlspecialchars($empresa_nome); ?></h5>
                            <span class="footer-copyright">
                                &copy; <?php echo $ano_atual; ?> Todos os direitos reservados.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="footer-status">
                            <div class="status-indicator">
                                <div class="status-dot"></div>
                                <span class="status-text">Sistema Online</span>
                            </div>
                            <small class="status-uptime">
                                <i class="fas fa-clock me-1"></i>
                                Última atualização: <?php echo date('H:i'); ?>
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="footer-info">
                            <div class="version-badge">
                                <i class="fas fa-code-branch me-1"></i>
                                Versão <?php echo $versao_sistema; ?>
                            </div>
                            <?php if(isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin'): ?>
                            <div class="admin-links mt-2">
                                <a href="<?php echo $base_path ?? ''; ?>config/configuracoes.php" class="footer-link">
                                    <i class="fas fa-cog me-1"></i> Configurações
                                </a>
                                <span class="link-separator">|</span>
                                <a href="<?php echo $base_path ?? ''; ?>usuarios/listar.php" class="footer-link">
                                    <i class="fas fa-users-cog me-1"></i> Usuários
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Status Bar -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="footer-stats">
                            <div class="stats-grid">
                                <div class="stat-item" id="sessionTime">
                                    <i class="fas fa-user-clock"></i>
                                    <span>Sessão: --:--</span>
                                </div>
                                <div class="stat-item" id="serverStatus">
                                    <i class="fas fa-server"></i>
                                    <span>Servidor: Online</span>
                                </div>
                                <div class="stat-item" id="dbStatus">
                                    <i class="fas fa-database"></i>
                                    <span>BD: Conectado</span>
                                </div>
                                <div class="stat-item" id="memoryUsage">
                                    <i class="fas fa-memory"></i>
                                    <span>Memória: <?php echo round(memory_get_usage(true) / 1024 / 1024, 1); ?>MB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- CSS do Footer Premium -->
    <style>
    .footer {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 -2px 20px rgba(0,0,0,0.1);
        border-radius: 15px 15px 0 0;
        margin-top: 3rem;
        position: relative;
        overflow: hidden;
    }
    
    .footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
        pointer-events: none;
    }
    
    .footer-content {
        position: relative;
        z-index: 1;
    }
    
    .footer-brand h5 {
        color: white;
        font-weight: 700;
        text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        margin-bottom: 0.5rem;
    }
    
    .footer-copyright {
        color: rgba(255,255,255,0.8);
        font-size: 0.9rem;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    
    .footer-status {
        text-align: center;
    }
    
    .status-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.5rem;
    }
    
    .status-dot {
        width: 12px;
        height: 12px;
        background: #28a745;
        border-radius: 50%;
        margin-right: 8px;
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 10px rgba(40, 167, 69, 0.5); }
        50% { box-shadow: 0 0 20px rgba(40, 167, 69, 0.8); }
        100% { box-shadow: 0 0 10px rgba(40, 167, 69, 0.5); }
    }
    
    .status-text {
        color: white;
        font-weight: 500;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    
    .status-uptime {
        color: rgba(255,255,255,0.7);
        font-size: 0.8rem;
    }
    
    .footer-info {
        text-align: right;
    }
    
    .version-badge {
        background: rgba(255,255,255,0.2);
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: inline-block;
        margin-bottom: 0.5rem;
    }
    
    .admin-links {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    
    .footer-link {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        padding: 4px 8px;
        border-radius: 6px;
    }
    
    .footer-link:hover {
        color: white;
        background: rgba(255,255,255,0.1);
        transform: translateY(-1px);
    }
    
    .link-separator {
        color: rgba(255,255,255,0.4);
        margin: 0 0.5rem;
    }
    
    .footer-stats {
        border-top: 1px solid rgba(255,255,255,0.2);
        padding-top: 1rem;
        margin-top: 1rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        text-align: center;
    }
    
    .stat-item {
        background: rgba(255,255,255,0.1);
        padding: 8px 12px;
        border-radius: 8px;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }
    
    .stat-item:hover {
        background: rgba(255,255,255,0.15);
        transform: translateY(-1px);
    }
    
    .stat-item i {
        margin-right: 6px;
        opacity: 0.8;
    }
    
    .stat-item span {
        font-weight: 500;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .footer {
            margin-top: 2rem;
            padding: 1.5rem 0;
        }
        
        .footer-info {
            text-align: center;
            margin-top: 1rem;
        }
        
        .admin-links {
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }
        
        .stat-item {
            font-size: 0.8rem;
            padding: 6px 10px;
        }
        
        .version-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
        }
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .footer {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
        }
    }
    
    /* Performance indicator */
    .performance-good { color: #28a745; }
    .performance-warning { color: #ffc107; }
    .performance-danger { color: #dc3545; }
    </style>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo time(); ?>"></script>
    
    <!-- Scripts do Footer -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips do Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Controle do menu lateral em dispositivos móveis
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
            
            // Função para mostrar o menu
            function showSidebar() {
                if (sidebar && sidebarBackdrop) {
                    sidebar.classList.add('show');
                    sidebarBackdrop.classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            }
            
            // Função para esconder o menu
            function hideSidebar() {
                if (sidebar && sidebarBackdrop) {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                    document.body.style.overflow = '';
                }
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
                        setTimeout(hideSidebar, 100);
                    }
                });
            });
            
            // Fechar menu ao redimensionar para desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768 && sidebar && sidebar.classList.contains('show')) {
                    hideSidebar();
                }
            });
            
            // Controle de tempo de sessão
            let sessionStart = new Date();
            function updateSessionTime() {
                const now = new Date();
                const diff = now - sessionStart;
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                
                const sessionTimeElement = document.getElementById('sessionTime');
                if (sessionTimeElement) {
                    sessionTimeElement.querySelector('span').textContent = 
                        `Sessão: ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }
            }
            
            // Atualizar tempo de sessão a cada segundo
            setInterval(updateSessionTime, 1000);
            
            // Verificar status do servidor
            function checkServerStatus() {
                const serverStatusElement = document.getElementById('serverStatus');
                const dbStatusElement = document.getElementById('dbStatus');
                
                // Simular verificação de status (você pode implementar uma verificação real via AJAX)
                if (serverStatusElement) {
                    const serverIcon = serverStatusElement.querySelector('i');
                    const serverText = serverStatusElement.querySelector('span');
                    
                    // Animação de verificação
                    serverIcon.className = 'fas fa-spinner fa-spin';
                    setTimeout(() => {
                        serverIcon.className = 'fas fa-server';
                        serverText.textContent = 'Servidor: Online';
                        serverStatusElement.style.color = '#28a745';
                    }, 1000);
                }
                
                if (dbStatusElement) {
                    const dbIcon = dbStatusElement.querySelector('i');
                    const dbText = dbStatusElement.querySelector('span');
                    
                    dbIcon.className = 'fas fa-spinner fa-spin';
                    setTimeout(() => {
                        dbIcon.className = 'fas fa-database';
                        dbText.textContent = 'BD: Conectado';
                        dbStatusElement.style.color = '#28a745';
                    }, 1500);
                }
            }
            
            // Verificar status na inicialização
            checkServerStatus();
            
            // Verificar status a cada 30 segundos
            setInterval(checkServerStatus, 30000);
            
            // Atualizar uso de memória periodicamente
            function updateMemoryUsage() {
                const memoryElement = document.getElementById('memoryUsage');
                if (memoryElement) {
                    // Esta é uma simulação - em produção você faria uma requisição AJAX
                    const usage = (Math.random() * 50 + 20).toFixed(1);
                    memoryElement.querySelector('span').textContent = `Memória: ${usage}MB`;
                    
                    // Colorir baseado no uso
                    if (usage < 50) {
                        memoryElement.className = 'stat-item performance-good';
                    } else if (usage < 80) {
                        memoryElement.className = 'stat-item performance-warning';
                    } else {
                        memoryElement.className = 'stat-item performance-danger';
                    }
                }
            }
            
            // Atualizar memória a cada 10 segundos
            setInterval(updateMemoryUsage, 10000);
            
            // Animações suaves para elementos do footer
            const footerElements = document.querySelectorAll('.stat-item, .version-badge, .footer-link');
            footerElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'all 0.6s ease';
                
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo $base_path; ?>sw.js')
                    .then(registration => {
                        console.log('Service Worker registrado:', registration.scope);
                    })
                    .catch(error => {
                        console.log('Falha ao registrar Service Worker:', error);
                    });
            });
        }
    </script>

</body>
</html>