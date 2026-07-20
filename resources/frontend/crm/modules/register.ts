export async function loadCrmModuleOverlays(): Promise<void> {
  if (window.__martinSolsCrmModulesLoaded) {
    return;
  }

  window.__martinSolsCrmModulesLoaded = true;

  await Promise.all([
    import('../../../../Modules/CrmCore/resources/assets/crm-dashboard.js'),
    import('../../../../Modules/CrmLeaves/resources/assets/crm-conges.js'),
    import('../../../../Modules/CrmCashControl/resources/assets/crm-controle-caisse.js'),
    import('../../../../Modules/CrmDepositRequests/resources/assets/crm-demandes-acompte.js'),
    import('../../../../Modules/CrmCheckRemittances/resources/assets/crm-remise-cheques.js'),
    import('../../../../Modules/CrmDocuments/resources/assets/crm-documents.js'),
    import('../../../../Modules/CrmSalesTours/resources/assets/crm-tournees-representants.js'),
    import('../../../../Modules/CrmPages/resources/assets/crm-pages.js'),
    import('../../../../Modules/CrmTeams/resources/assets/crm-equipes.js'),
    import('../../../../Modules/CrmCore/resources/assets/crm-account-settings.js'),
  ]);
}

export async function loadCrmShellOverlays(): Promise<void> {
  await Promise.all([
    import('../../../../Modules/CrmCore/resources/assets/crm-active-site.js'),
    import('../../../../Modules/CrmCore/resources/assets/crm-text-fixes.js'),
  ]);
}

export async function loadBrandMorphLoader(): Promise<void> {
  await import('../../../../Modules/CrmCore/resources/assets/brand-morph-loader.js');
  await import('../../../../Modules/CrmCore/resources/assets/brand-morph-loader-app.js');
}
