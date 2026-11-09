// O expo-secure-store em memória (o chaveiro não existe no Jest), instalado
// pelo jest.setup.ts, que o esvazia antes de cada teste.

const items = new Map<string, string>();

export const AFTER_FIRST_UNLOCK = 'AFTER_FIRST_UNLOCK';

export const getItemAsync = jest.fn(async (key: string) => items.get(key) ?? null);

export const setItemAsync = jest.fn(async (key: string, value: string) => {
  items.set(key, value);
});

export const deleteItemAsync = jest.fn(async (key: string) => {
  items.delete(key);
});

export function resetSecureStore() {
  items.clear();
  getItemAsync.mockClear();
  setItemAsync.mockClear();
  deleteItemAsync.mockClear();
}

export function secureItems(): Map<string, string> {
  return items;
}
