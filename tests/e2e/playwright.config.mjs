import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 180_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: 'list',
  use: {
    baseURL: 'http://localhost:8585',
    headless: process.env.HEADED !== '1',
    launchOptions: {
      slowMo: Number(process.env.SLOWMO || 0),
    },
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
  },
});
