import { Outlet, Link, useLocation } from 'react-router'
import { useTheme } from '@/hooks/useTheme'
import { Icon, Icons, Logo } from '@/components/common'
import { navLinkClasses } from '@/layouts/sidebar/navClasses'

interface NavItem {
  path: string
  label: string
  icon: string
}

/**
 * Dashboard Layout Component
 * Supports vertical (sidebar) and horizontal (top nav) layouts
 */
export function DashboardLayout() {
  const location = useLocation()
  const { config } = useTheme()

  const navItems: NavItem[] = [
    { path: '/dashboard', label: 'Dashboard', icon: Icons.dashboard },
    // Add more nav items as you build
  ]

  const isActive = (path: string) => {
    if (path === '/dashboard') {
      return location.pathname === '/dashboard'
    }
    return location.pathname.startsWith(path)
  }

  const isHorizontal = config.sidebarLayout === 'horizontal'
  const isRtl = config.direction === 'rtl'

  return (
    <div className="min-h-screen bg-surface-100 dark:bg-surface-950">
      {/* Header - Always on top */}
      <header
        className="layout-header fixed top-0 right-0 h-[70px] bg-white dark:bg-surface-900 border-b border-surface-200 dark:border-surface-800 flex items-center justify-between px-6 z-[1020]"
        style={
          isHorizontal
            ? { left: 0, right: 0 }
            : isRtl
              ? { left: 0, right: 'var(--sidebar-width)' }
              : { left: 'var(--sidebar-width)' }
        }
      >
        <div className="flex items-center gap-4">
          {isHorizontal && (
            <Link to="/" className="me-8">
              <Logo width={120} height={24} />
            </Link>
          )}
          <h1 className="text-lg font-semibold text-secondary-900 dark:text-white">
            Dashboard
          </h1>
        </div>
        <div className="flex items-center gap-4">
          <div className="w-10 h-10 rounded-full bg-theme-primary flex items-center justify-center text-white text-sm font-medium">
            U
          </div>
        </div>
      </header>

      {/* Sidebar / Horizontal Nav */}
      <aside className={`layout-sidebar bg-white dark:bg-surface-900 border-surface-200 dark:border-surface-800 flex transition-all duration-300 ${
        isHorizontal
          ? 'border-b flex-row items-center px-6 gap-1'
          : 'border-e flex-col'
      }`}>
        {/* Logo - Vertical only */}
        {!isHorizontal && (
          <div className="h-[70px] flex items-center px-6 border-b border-surface-200 dark:border-surface-800">
            <Link to="/" className="flex items-center gap-2">
              <Logo width={120} height={24} />
            </Link>
          </div>
        )}

        {/* Navigation */}
        <nav className={`${isHorizontal ? 'flex items-center gap-1' : 'flex-1 p-4 space-y-1'}`}>
          {navItems.map((item) => (
            <Link
              key={item.path}
              to={item.path}
              className={navLinkClasses(isActive(item.path), false)}
            >
              <Icon icon={item.icon} className="w-5 h-5" />
              {item.label}
            </Link>
          ))}
        </nav>

        {/* Footer - Vertical only */}
        {!isHorizontal && (
          <div className="p-4 border-t border-surface-200 dark:border-surface-800">
            <Link
              to="/auth/login"
              className={navLinkClasses(false, false)}
            >
              <Icon icon={Icons.logout} className="w-5 h-5" />
              Logout
            </Link>
          </div>
        )}
      </aside>

      {/* Main Content */}
      <main
        className={`layout-main transition-all duration-300 ${isHorizontal ? 'pt-[130px]' : 'pt-[70px]'}`}
        style={
          isHorizontal
            ? { marginLeft: 0, marginRight: 0 }
            : isRtl
              ? { marginLeft: 0, marginRight: 'var(--sidebar-width)' }
              : { marginLeft: 'var(--sidebar-width)' }
        }
      >
        <div className="layout-container layout-page">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
