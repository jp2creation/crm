import js from '@eslint/js';
import globals from 'globals';
import tseslint from 'typescript-eslint';
import { defineConfig, globalIgnores } from 'eslint/config';

export default defineConfig([
    globalIgnores([
        'node_modules',
        'public',
        'vendor',
        'resources/frontend/adminex',
    ]),
    {
        files: ['resources/frontend/crm/**/*.{ts,tsx}'],
        extends: [
            js.configs.recommended,
            ...tseslint.configs.recommended,
        ],
        languageOptions: {
            ecmaVersion: 2022,
            globals: globals.browser,
            parserOptions: {
                project: './tsconfig.frontend.json',
            },
        },
        rules: {
            '@typescript-eslint/no-explicit-any': 'error',
        },
    },
]);
