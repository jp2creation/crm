import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    include: ['resources/frontend/crm/**/*.test.ts'],
  },
});
