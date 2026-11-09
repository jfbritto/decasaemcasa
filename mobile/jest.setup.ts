// Setup global do Jest. Os matchers do RNTL (toBeOnTheScreen...) entram com
// qualquer import de '@testing-library/react-native'.

// SafeAreaView/useSafeAreaInsets pedem o provider nativo: o mock da biblioteca.
jest.mock('react-native-safe-area-context', () => require('react-native-safe-area-context/jest/mock').default);

// O chaveiro não existe no Jest: o SecureStore em memória, esvaziado antes
// de cada teste.
jest.mock('expo-secure-store', () => require('./src/test-utils/secure-store-mock'));

// Não é um celular de verdade: sem push (services/push nunca carrega o
// expo-notifications aqui).
jest.mock('expo-device', () => ({ isDevice: false, deviceName: 'Celular de teste', modelName: 'Teste' }));

// A imagem da capa: um View com as props.
jest.mock('expo-image', () => {
  const { View } = require('react-native');
  return { Image: (props: object) => require('react').createElement(View, { ...props, testID: 'expo-image' }) };
});

jest.mock('expo-clipboard', () => ({ setStringAsync: jest.fn(async () => true) }));

// O upload nativo do comprovante (services/api-upload): cada teste diz a
// resposta em uploadMock.
jest.mock('expo-file-system', () => {
  const uploadMock = jest.fn(async () => ({ status: 200, body: '{}', headers: {} }));
  class File {
    uri: string;
    constructor(uri: string) {
      this.uri = uri;
    }
    upload(url: string, options: unknown) {
      return uploadMock.call(this, url, options);
    }
  }
  return { File, UploadType: { BINARY_CONTENT: 0, MULTIPART: 1 }, uploadMock };
});
jest.mock('expo-web-browser', () => ({ openBrowserAsync: jest.fn(async () => ({ type: 'opened' })) }));

jest.mock('expo-image-picker', () => ({
  requestCameraPermissionsAsync: jest.fn(async () => ({ granted: true, canAskAgain: true })),
  launchCameraAsync: jest.fn(async () => ({ canceled: true, assets: null })),
  launchImageLibraryAsync: jest.fn(async () => ({ canceled: true, assets: null })),
}));
jest.mock('expo-document-picker', () => ({
  getDocumentAsync: jest.fn(async () => ({ canceled: true, assets: null })),
}));
jest.mock('expo-image-manipulator', () => {
  const context = {
    resize: jest.fn(),
    release: jest.fn(),
    renderAsync: jest.fn(async () => ({
      width: 1600,
      height: 1200,
      release: jest.fn(),
      saveAsync: jest.fn(async () => ({ uri: 'file:///cache/reduzida.jpg', width: 1600, height: 1200 })),
    })),
  };
  return {
    SaveFormat: { JPEG: 'jpeg', PNG: 'png' },
    ImageManipulator: { manipulate: jest.fn(() => context) },
    __context: context,
  };
});

// Uma consulta sem tela agenda a limpeza para gcTime depois: um timer vivo
// que segurava os workers do Jest. Os testes limpam o cache eles mesmos.
{
  const { queryClient } = require('./src/lib/query-client');
  const defaults = queryClient.getDefaultOptions();
  queryClient.setDefaultOptions({ ...defaults, queries: { ...defaults.queries, gcTime: Infinity, retry: false } });
}

beforeEach(() => {
  require('./src/test-utils/secure-store-mock').resetSecureStore();
  require('./src/lib/query-client').queryClient.clear();
  require('./src/stores/auth-store').resetAuthStore();
});
