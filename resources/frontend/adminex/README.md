<div align="center">

# Adminex

### React Admin Dashboard Platform

[![React](https://img.shields.io/badge/React-19.2.0-61DAFB?style=flat&logo=react)](https://reactjs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.9.3-3178C6?style=flat&logo=typescript)](https://www.typescriptlang.org/)
[![Vite](https://img.shields.io/badge/Vite-7.2.4-646CFF?style=flat&logo=vite)](https://vite.dev/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.1.17-38B2AC?style=flat&logo=tailwindcss)](https://tailwindcss.com/)

**A modern, feature-rich React admin dashboard platform built with the latest technologies.**

[Live Demo](#) • [Documentation](#) • [Get Support](mailto:support@devcraftify.com)

</div>

---

## 🎯 Overview

Adminex is a premium React admin dashboard platform designed for building modern web applications. Built with React 19, TypeScript, and Tailwind CSS 4, it provides everything you need to create stunning admin panels, dashboards, and complex business applications.

## ✨ Key Features

### 🎨 Modern Design
- Clean and professional UI design
- Dark mode support
- 6 color themes (Royal Blue, Teal, Purple, Orange, Indigo, Rose)
- Fully responsive for all screen sizes

### 📊 4 Dashboard Variants
- **Overview Dashboard** - General KPIs and summary metrics
- **Analytics Dashboard** - Data visualization and metrics
- **CRM Dashboard** - Customer relationship management
- **E-commerce Dashboard** - Sales and product metrics

### 📱 8 Complete Applications
- Email App with inbox, compose, and folders
- Chat App with real-time messaging UI
- Calendar App with event management
- Kanban Board with drag-and-drop
- Notes App with categories
- Contacts App with CRUD operations
- E-commerce (Products, Cart, Checkout, Orders)
- Blog with posts and details

### 🧠 8 Advanced Features (Complex Logic)
- **Rule Engine** - Visual rule builder with conditional logic and nested groups
- **Query Builder** - Dynamic query construction with SQL/JSON preview
- **Real-Time Simulation** - Live data streaming dashboards with real-time charts
- **Smart Insights** - AI-powered analytics with anomaly detection and trends
- **Workflow Builder** - Node-based workflow designer with branching logic
- **Approval Engine** - State machine-driven approval workflows
- **Task Scheduler** - Gantt charts, dependencies, and cron scheduling
- **Notification Pipeline** - Multi-channel notifications with routing rules

### 🌍 Internationalization (i18n)
- 10 languages included
- RTL (Right-to-Left) support for Arabic and Urdu
- Easy to add more languages

### 📈 Chart Components
- Line Charts
- Bar/Column Charts
- Area Charts
- Pie & Doughnut Charts
- Radar Charts
- Candlestick Charts

### 🔐 Authentication Pages
- Login page
- Register page
- Forgot password
- Lock screen
- Modern auth layouts

### 🛠️ Developer Experience
- TypeScript with strict mode
- ESLint configured
- Clean project structure
- Well-documented code

## 🚀 Quick Start

### Prerequisites

- Node.js 18+
- npm, yarn, or pnpm

### Installation

1. **Extract the package**
   ```bash
   unzip adminex-pro.zip
   cd adminex-pro
   ```

2. **Install dependencies**
   ```bash
   npm install
   # or
   yarn install
   # or
   pnpm install
   ```

3. **Start development server**
   ```bash
   npm run dev
   # or
   yarn dev
   # or
   pnpm dev
   ```

4. **Open in browser**
   ```
   http://localhost:5173
   ```

### Build for Production

```bash
npm run build
```

The production build will be in the `dist` folder.

### Preview Production Build

```bash
npm run preview
```

## 📁 Project Structure

```
adminex-pro/
├── public/              # Static assets
│   └── assets/
│       ├── avatars/     # Avatar images (15 AI-generated images)
│       ├── blogs/       # Blog cover images (AI-generated)
│       ├── products/    # Product images (AI-generated)
│       ├── flags/       # Flag icons
│       ├── landing/     # Landing page assets
│       └── logo/        # Logo files
├── src/
│   ├── components/      # Reusable UI components
│   ├── context/         # React context providers
│   ├── data/            # Static/mock data
│   ├── features/         # Advanced feature modules
│   │   ├── approval-engine/
│   │   ├── notification-pipeline/
│   │   ├── query-builder/
│   │   ├── real-time-simulation/
│   │   ├── rule-engine/
│   │   ├── smart-insights/
│   │   ├── task-scheduler/
│   │   └── workflow-builder/
│   ├── hooks/           # Custom React hooks
│   ├── i18n/            # Internationalization
│   │   └── locales/     # Translation files
│   ├── layouts/         # Page layouts
│   ├── pages/           # Page components
│   ├── routes/          # Routing configuration
│   ├── styles/          # Global styles
│   └── types/           # TypeScript types
├── package.json
├── vite.config.ts
├── tsconfig.json
├── tailwind.config.js
└── README.md
```

## 🎨 Customization

### Using or Replacing Images

This template includes AI-generated sample images in `/public/assets/`:

**Included Images:**
- **15 avatar images** - User profile photos
- **10 blog cover images** - Blog post headers (800x400px)
- **45 product images** - E-commerce product photos (3 angles per product, 800x800px)

**Usage:**
- ✅ Use as-is in your projects
- ✅ Modify or replace with your own images
- ✅ No attribution required
- ✅ No copyright restrictions

**To replace images:**
```bash
# Replace blog cover
cp your-image.jpg public/assets/blogs/cover-1.jpg

# Replace product image
cp your-product.jpg public/assets/products/product-1-main.jpg
```

The data files in `src/data/` reference these images by path, so simply replacing the files will update them throughout the app.

### Changing Theme Colors

The template uses CSS variables for theming. You can customize colors in:
- `src/styles/variables.css` - CSS custom properties
- Theme customizer panel in the UI

### Adding a New Page

1. Create your page component in `src/pages/`
2. Add the route in `src/routes/index.tsx`
3. Add navigation item in `src/layouts/sidebar/navData.ts`

### Adding a New Language

1. Create a new JSON file in `src/i18n/locales/` (e.g., `de.json`)
2. Copy structure from `en.json` and translate
3. Register in `src/i18n/index.ts`

## 🛠️ Tech Stack

| Technology | Version | Description |
|------------|---------|-------------|
| React | 19.2.0 | UI library |
| TypeScript | 5.9.3 | Type-safe JavaScript |
| Vite | 7.2.4 | Build tool |
| Tailwind CSS | 4.1.17 | Utility-first CSS |
| React Router | 7.9.6 | Client-side routing |
| Chart.js | 4.5.1 | Charts library |
| DND Kit | 6.3.1 | Drag and drop |
| Tiptap | 3.14.0 | Rich text editor |
| Swiper | 12.0.3 | Touch slider |

## 📄 Included Pages

### Dashboards
- Overview
- Analytics
- CRM
- E-commerce

### Applications
- Email
- Chat
- Calendar
- Kanban
- Notes
- Contacts
- E-commerce (Products, Cart, Checkout, Orders, Customers)
- Blog

### Advanced Features
- Rule Engine
- Query Builder
- Real-Time Simulation
- Smart Insights
- Workflow Builder
- Approval Engine
- Task Scheduler
- Notification Pipeline

### Authentication
- Login
- Register
- Forgot Password
- Lock Screen

### Utility Pages
- Pricing (2 variants)
- FAQ
- Team Members
- User Profile
- Timeline
- Account Settings

### Error Pages
- 404 Not Found
- 403 Forbidden
- 500 Server Error
- 503 Service Unavailable
- Maintenance

### Charts
- Line Charts
- Area Charts
- Column Charts
- Candlestick Charts
- Pie Charts
- Radial Charts

### Forms
- Basic Inputs
- Select Components
- Radio & Checkbox
- Textarea
- File Upload
- Form Wizard
- Form Layouts

### Tables
- Basic Tables
- Striped Tables
- Bordered Tables
- Hoverable Tables
- Responsive Tables

## ⚠️ Important Notes

### About Images
This template includes **AI-generated sample images** (blog covers, product images, avatars) that are included in the source code with **no copyright restrictions**. These images are free to use, modify, and include in your projects. You may use them as-is or replace them with your own images.

### Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## 📞 Support

For support, please contact us at: **support@devcraftify.com**

Support includes:
- Bug fixes
- Clarification on usage
- Help with installation

Support does NOT include:
- Customization beyond the core product
- Third-party plugin support
- Installation on your server

## 📝 License

This item is licensed under the Envato Market License. See [LICENSE.txt](LICENSE.txt) for details.

## 🔄 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## 🙏 Credits

See [CREDITS.md](CREDITS.md) for third-party asset attributions.

---

<div align="center">

**Made with ❤️ by DevCraftify**

[Purchase on ThemeForest](#) • [Live Demo](#) • [Documentation](#)

</div>
