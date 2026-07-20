import { Icons } from '@/components/common'
import type { NavGroup } from './types'

/**
 * Navigation menu configuration
 * Centralized navigation data for the sidebar
 */
export const navGroups: NavGroup[] = [
  {
    title: 'Dashboards',
    items: [
      { path: '/dashboard', label: 'Overview', icon: Icons.dashboard },
      { path: '/dashboard/analytics', label: 'Analytics', icon: Icons.chartLine },
      { path: '/dashboard/ecommerce', label: 'eCommerce', icon: Icons.shopping },
      { path: '/dashboard/crm', label: 'CRM', icon: Icons.briefcase },
    ],
  },
  {
    title: 'Apps',
    items: [
      { path: '/app/email', label: 'Email', icon: Icons.mail, badge: 3 },
      { path: '/app/chat', label: 'Chat', icon: Icons.message, badge: 5 },
      { path: '/app/calendar', label: 'Calendar', icon: Icons.calendar },
      { path: '/app/contacts', label: 'Contacts', icon: Icons.contacts },
      {
        path: '/app/blog',
        label: 'Blog',
        icon: Icons.article,
        children: [
          { path: '/app/blog', label: 'All Posts' },
          { path: '/app/blog/create', label: 'Create Post' },
        ]
      },
      {
        path: '/app/ecommerce/products',
        label: 'E-commerce',
        icon: Icons.shopping,
        children: [
          { path: '/app/ecommerce/products', label: 'Products' },
          { path: '/app/ecommerce/products/create', label: 'Add Product' },
          { path: '/app/ecommerce/checkout', label: 'Checkout' },
        ]
      },
      { path: '/app/notes', label: 'Notes', icon: Icons.note },
      { path: '/app/kanban', label: 'Kanban Board', icon: Icons.kanban },
      // Features (Complex Logic)
      { path: '/features/rule-engine', label: 'Rule Engine', icon: Icons.ruleEngine },
      { path: '/features/query-builder', label: 'Query Builder', icon: Icons.queryBuilder },
      { path: '/features/simulation', label: 'Real-Time Simulation', icon: Icons.simulation },
      { path: '/features/insights', label: 'Smart Insights', icon: Icons.insights },
      { path: '/features/workflow-builder', label: 'Workflow Builder', icon: Icons.workflowBuilder },
      { path: '/features/task-scheduler', label: 'Task Scheduler', icon: Icons.taskScheduler },
    ],
  },
  {
    title: 'Authentication',
    items: [
      {
        path: '/auth/login',
        label: 'Login',
        icon: Icons.lock,
        children: [
          { path: '/auth/login', label: 'Side Login' },
          { path: '/auth-card/login', label: 'Card Login' },
        ]
      },
      {
        path: '/auth/register',
        label: 'Register',
        icon: Icons.userPlus,
        children: [
          { path: '/auth/register', label: 'Side Register' },
          { path: '/auth-card/register', label: 'Card Register' },
        ]
      },
      { path: '/auth/forgot-password', label: 'Forgot Password', icon: Icons.key },
    ],
  },
  {
    title: 'Pages',
    items: [
      { path: '/pages/pricing', label: 'Pricing', icon: Icons.creditCard },
      { path: '/pages/account-settings', label: 'Account Settings', icon: Icons.settings },
      { path: '/pages/gallery', label: 'Gallery', icon: Icons.photo },
      { path: '/pages/faq', label: 'FAQ', icon: Icons.help },
      { path: '/pages/typography', label: 'Typography', icon: Icons.heading },
    ],
  },
  {
    title: 'From',
    items: [
      { path: '/forms/layout', label: 'Form Layout', icon: Icons.layoutGrid },
      { path: '/forms/validation', label: 'Form Validation', icon: Icons.checklist },
      { path: '/forms/editor', label: 'Editor', icon: Icons.edit },
    ],
  },
  {
    title: 'Table',
    items: [
      { path: '/tables/simple', label: 'Simple Table', icon: Icons.table },
      { path: '/tables/data', label: 'Data Table', icon: Icons.database },
      { path: '/tables/crud', label: 'CRUD Table', icon: Icons.edit },
    ],
  },
  {
    title: 'Charts',
    items: [
      { path: '/charts/line', label: 'Line', icon: Icons.chartLine },
      { path: '/charts/area', label: 'Area', icon: Icons.chartArea },
      { path: '/charts/columns', label: 'Columns', icon: Icons.chartBar },
      { path: '/charts/pie', label: 'Pie & Doughnut', icon: Icons.chartPie },
      { path: '/charts/radar', label: 'Radar', icon: Icons.chartRadar },
      { path: '/charts/candlestick', label: 'Candlestick', icon: Icons.chartCandle },
    ],
  },
]
