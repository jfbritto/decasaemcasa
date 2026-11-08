/** @type {import('jest').Config} */
module.exports = {
  // O preset jest-expo já transpila o Expo e o react-native-svg; não mexer
  // nos transformIgnorePatterns dele.
  preset: 'jest-expo',
  setupFilesAfterEnv: ['<rootDir>/jest.setup.ts'],
  testPathIgnorePatterns: ['/node_modules/', '/.expo/', '/android/', '/ios/'],
  // As telas renderizam a navegação de verdade: 15 s mostram um travamento
  // real sem falhar à toa numa máquina carregada (como no HelpCheck).
  testTimeout: 15000,
};
