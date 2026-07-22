type CrmModuleHostPageProps = {
  className: string;
  id: string;
  label?: string;
};

export function CrmModuleHostPage({ className, id, label }: CrmModuleHostPageProps) {
  return <div id={id} className={className} aria-label={label} />;
}

export function CrmPilotageCommercialPage() {
  return <CrmModuleHostPage id="crm-sales-module" className="crm-sales-module-host" label="Pilotage commercial" />;
}

export function CrmHostPage(props: CrmModuleHostPageProps) {
  return <CrmModuleHostPage {...props} />;
}
