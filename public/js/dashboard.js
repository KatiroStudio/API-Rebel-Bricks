// Fonction pour mettre à jour le statut
function updateStatus(elementId, isOnline, responseTime = null) {
    console.log('updateStatus appelé avec:', { elementId, isOnline, responseTime });
    const element = document.getElementById(elementId);
    console.log('Élément trouvé:', element);
    
    if (element) {
        console.log('Classes avant mise à jour:', element.classList);
        element.classList.remove('status-online', 'status-offline');
        element.classList.add(isOnline ? 'status-online' : 'status-offline');
        console.log('Classes après mise à jour:', element.classList);
        
        const icon = element.querySelector('i');
        const text = element.querySelector('span');
        console.log('Icone trouvée:', icon);
        console.log('Texte trouvé:', text);
        
        if (isOnline) {
            icon.className = 'bi bi-check2-circle';
            text.textContent = `Système opérationnel${responseTime ? ` (${responseTime}ms)` : ''}`;
        } else {
            icon.className = 'bi bi-x-circle';
            text.textContent = 'Système hors ligne';
        }
        
        console.log('Classes finales:', element.classList);
        console.log('Texte final:', text.textContent);
    } else {
        console.error('Élément non trouvé:', elementId);
    }
}

// Fonction pour mettre à jour les statistiques
function updateStats(data) {
    const tableBody = document.getElementById('stats-body');
    if (!data || !Array.isArray(data)) {
        tableBody.innerHTML = '<tr><td colspan="5">Aucune donnée disponible</td></tr>';
        return;
    }
    tableBody.innerHTML = data.map(row => `
        <tr>
            <td>${row.api}</td>
            <td>${row.total_requests}</td>
            <td>${row.successful_requests}</td>
            <td>${row.failed_requests}</td>
            <td>${row.success_rate}%</td>
        </tr>
    `).join('');
}

// Fonction pour gérer les erreurs de requête
async function handleRequest(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return { success: true, data };
    } catch (error) {
        console.warn(`Erreur lors de la requête à ${url}:`, error);
        return { success: false, error };
    }
}

// Vérifier le statut des API
let bricksetChecked = false;
let rebrickableChecked = false;
let supabaseChecked = false;

async function checkAPIs() {
    try {
        // Vérifier Brickset
        console.log('Vérification de Brickset API...');
        try {
            const startTime = performance.now();
            const bricksetResponse = await fetch('/RB_API Rebel Bricks/public/controllers/BricksetController.php?action=themes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const endTime = performance.now();
            const responseTime = Math.round(endTime - startTime);
            
            console.log('Réponse Brickset:', bricksetResponse);
            
            if (!bricksetResponse.ok) {
                throw new Error(`HTTP error! status: ${bricksetResponse.status}`);
            }
            
            const bricksetData = await bricksetResponse.json();
            console.log('Données Brickset:', bricksetData);
            
            // Vérifier si la réponse est valide
            const isBricksetOnline = bricksetData && bricksetData.success && Array.isArray(bricksetData.data) && bricksetData.data.length > 0;
            console.log('Statut Brickset:', isBricksetOnline);
            
            // Mettre à jour le statut avec le temps de réponse
            updateStatus('brickset-status', isBricksetOnline, responseTime);
            bricksetChecked = true;

            if (isBricksetOnline) {
                const themes = bricksetData.data;
                console.log('Thèmes Brickset:', themes);
                
                const stats = {
                    totalThemes: themes.length,
                    totalSets: themes.reduce((sum, theme) => sum + (parseInt(theme.setCount) || 0), 0),
                    oldestYear: Math.min(...themes.map(theme => parseInt(theme.yearFrom) || Infinity)),
                    newestYear: Math.max(...themes.map(theme => parseInt(theme.yearTo) || -Infinity))
                };

                document.getElementById('total-themes').textContent = stats.totalThemes;
                document.getElementById('total-sets').textContent = stats.totalSets.toLocaleString();
                document.getElementById('oldest-year').textContent = stats.oldestYear === Infinity ? '-' : stats.oldestYear;
                document.getElementById('newest-year').textContent = stats.newestYear === -Infinity ? '-' : stats.newestYear;
            } else {
                console.error('Erreur dans les données Brickset:', bricksetData);
                document.getElementById('total-themes').textContent = '-';
                document.getElementById('total-sets').textContent = '-';
                document.getElementById('oldest-year').textContent = '-';
                document.getElementById('newest-year').textContent = '-';
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de Brickset:', error);
            updateStatus('brickset-status', false);
            bricksetChecked = true;
            document.getElementById('total-themes').textContent = '-';
            document.getElementById('total-sets').textContent = '-';
            document.getElementById('oldest-year').textContent = '-';
            document.getElementById('newest-year').textContent = '-';
        }

        // Vérifier Rebrickable
        console.log('Vérification de Rebrickable API...');
        try {
            const startTime = performance.now();
            // Récupérer les thèmes
            const themesResponse = await fetch('/RB_API Rebel Bricks/public/controllers/RebrickableController.php?action=themes', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-cache'
            });
            
            // Récupérer les statistiques des sets
            const setsStatsResponse = await fetch('/RB_API Rebel Bricks/public/controllers/RebrickableController.php?action=sets_stats', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-cache'
            });
            const endTime = performance.now();
            const responseTime = Math.round(endTime - startTime);
            
            if (!themesResponse.ok || !setsStatsResponse.ok) {
                throw new Error('Erreur lors de la récupération des données');
            }
            
            const themesData = await themesResponse.json();
            const setsStatsData = await setsStatsResponse.json();
            
            console.log('Données Thèmes Rebrickable:', themesData);
            console.log('Données Stats Sets Rebrickable:', setsStatsData);
            
            // Vérifier si la réponse est valide
            const isRebrickableOnline = themesData.success && 
                                      themesData.data && 
                                      (Array.isArray(themesData.data.results) || 
                                       Array.isArray(themesData.data));
            
            console.log('Statut Rebrickable:', isRebrickableOnline);
            rebrickableChecked = true;

            // Mettre à jour le statut une seule fois
            updateStatus('rebrickable-status-details', isRebrickableOnline, responseTime);

            if (isRebrickableOnline) {
                const themes = Array.isArray(themesData.data.results) ? 
                             themesData.data.results : 
                             themesData.data;
                             
                console.log('Thèmes Rebrickable (détaillé):', themes.map(theme => ({
                    name: theme.name,
                    setCount: theme.setCount || theme.set_count
                })));
                
                // Calculer les statistiques
                const stats = {
                    totalThemes: themes.length,
                    totalSets: setsStatsData.success ? setsStatsData.data.totalSets : 0,
                    oldestYear: setsStatsData.success ? setsStatsData.data.oldestYear : null,
                    newestYear: setsStatsData.success ? setsStatsData.data.newestYear : null
                };

                console.log('Statistiques Rebrickable:', stats);

                document.getElementById('rebrickable-total-themes').textContent = stats.totalThemes;
                document.getElementById('rebrickable-total-sets').textContent = stats.totalSets.toLocaleString();
                document.getElementById('rebrickable-oldest-year').textContent = stats.oldestYear || '-';
                document.getElementById('rebrickable-newest-year').textContent = stats.newestYear || '-';
            } else {
                console.error('Erreur dans les données Rebrickable:', themesData);
                document.getElementById('rebrickable-total-themes').textContent = '-';
                document.getElementById('rebrickable-total-sets').textContent = '-';
                document.getElementById('rebrickable-oldest-year').textContent = '-';
                document.getElementById('rebrickable-newest-year').textContent = '-';
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de Rebrickable:', error);
            updateStatus('rebrickable-status-details', false);
            rebrickableChecked = true;
            document.getElementById('rebrickable-total-themes').textContent = '-';
            document.getElementById('rebrickable-total-sets').textContent = '-';
            document.getElementById('rebrickable-oldest-year').textContent = '-';
            document.getElementById('rebrickable-newest-year').textContent = '-';
        }

        // Vérifier Supabase
        console.log('Vérification de Supabase...');
        try {
            const startTime = performance.now();
            const supabaseResponse = await fetch('/RB_API Rebel Bricks/public/controllers/SupabaseController.php?action=status', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const endTime = performance.now();
            const responseTime = Math.round(endTime - startTime);
            
            console.log('Réponse Supabase:', supabaseResponse);
            const supabaseData = await supabaseResponse.json();
            console.log('Données Supabase:', supabaseData);
            
            const isSupabaseOnline = supabaseData.success;
            updateStatus('supabase-status', isSupabaseOnline, responseTime);
            supabaseChecked = true;

            if (isSupabaseOnline) {
                console.log('Données Supabase complètes:', supabaseData);
                console.log('Stats Supabase:', supabaseData.stats);

                // Mettre à jour les statistiques
                document.getElementById('supabase-total-sets').textContent = supabaseData.stats.totalSets;
                document.getElementById('supabase-total-themes').textContent = supabaseData.stats.totalThemes;

                // Mettre à jour la date de dernière mise à jour
                const lastUpdateElement = document.getElementById('supabase-last-update');
                if (lastUpdateElement && supabaseData.stats.lastUpdate) {
                    const lastUpdateDate = new Date(supabaseData.stats.lastUpdate);
                    const formattedDate = lastUpdateDate.toLocaleString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    lastUpdateElement.querySelector('span').textContent = `Dernière mise à jour : ${formattedDate}`;
                }
            } else {
                console.error('Erreur dans les données Supabase:', supabaseData);
                document.getElementById('supabase-total-sets').textContent = '-';
                document.getElementById('supabase-total-themes').textContent = '-';
                const lastUpdateElement = document.getElementById('supabase-last-update');
                if (lastUpdateElement) {
                    const span = lastUpdateElement.querySelector('span');
                    span.textContent = 'Dernière mise à jour : -';
                    lastUpdateElement.classList.remove('status-online');
                    lastUpdateElement.classList.add('status-offline');
                }
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de Supabase:', error);
            updateStatus('supabase-status', false);
            supabaseChecked = true;
            document.getElementById('supabase-total-sets').textContent = '-';
            document.getElementById('supabase-total-themes').textContent = '-';
        }
    } catch (error) {
        console.error('Erreur lors de la vérification des APIs:', error);
        // Ne mettre à jour que les statuts des APIs qui n'ont pas encore été vérifiées
        if (!bricksetChecked) {
            updateStatus('brickset-status', false);
        }
        if (!rebrickableChecked) {
            updateStatus('rebrickable-status-details', false);
        }
        if (!supabaseChecked) {
            updateStatus('supabase-status', false);
        }
    }
}

// Vérifier les API toutes les 30 secondes
checkAPIs();
setInterval(checkAPIs, 30000); 