type CrmModuleHostPageProps = {
  className: string
  id: string
  label: string
}

export function CrmModuleHostPage({ className, id, label }: CrmModuleHostPageProps) {
  return (
    <div id={id} className={className}>
      {label}
    </div>
  )
}

export function CrmPilotageCommercialPage() {
  return (
    <CrmModuleHostPage
      id="crm-sales-module"
      className="crm-sales-module-host"
      label="Chargement du pilotage commercial..."
    />
  )
}
