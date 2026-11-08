import * as DocumentPicker from 'expo-document-picker';
import { ImageManipulator, SaveFormat } from 'expo-image-manipulator';
import * as ImagePicker from 'expo-image-picker';

import type { ProofFile } from '@/services/inscriptions';

// O comprovante do Pix (spec 2026-11-08-app-mobile-design, AM19): foto da
// câmera ou da galeria, reduzida a no máximo 1600 px em JPEG (um print de
// tela de 4 MB vira ~300 KB num sinal fraco), ou um arquivo (PDF, JPG, PNG)
// de até 5 MB, o limite do servidor.

export const PROOF_MAX_BYTES = 5 * 1024 * 1024;
export const PROOF_MAX_SIDE = 1600;

export class ProofPickError extends Error {
  readonly reason: 'permission' | 'too_large';

  constructor(reason: 'permission' | 'too_large', message: string) {
    super(message);
    this.name = 'ProofPickError';
    this.reason = reason;
  }
}

const PICKER_OPTIONS: ImagePicker.ImagePickerOptions = {
  mediaTypes: ['images'],
  quality: 1,
  allowsEditing: false,
  exif: false,
};

/** Null quando a pessoa desiste. */
export async function pickProofPhoto(source: 'camera' | 'library'): Promise<ProofFile | null> {
  if (source === 'camera') {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) {
      throw new ProofPickError('permission', 'Libere o acesso à câmera nos ajustes do celular para fotografar o comprovante.');
    }
  }

  const result =
    source === 'camera' ? await ImagePicker.launchCameraAsync(PICKER_OPTIONS) : await ImagePicker.launchImageLibraryAsync(PICKER_OPTIONS);
  const asset = result.canceled ? null : result.assets?.[0];
  if (!asset) return null;

  return { uri: await shrink(asset), name: 'comprovante.jpg', mimeType: 'image/jpeg' };
}

/** Null quando a pessoa desiste. */
export async function pickProofDocument(): Promise<ProofFile | null> {
  const result = await DocumentPicker.getDocumentAsync({
    type: ['application/pdf', 'image/jpeg', 'image/png'],
    copyToCacheDirectory: true,
    multiple: false,
  });
  const asset = result.canceled ? null : result.assets?.[0];
  if (!asset) return null;
  if (asset.size && asset.size > PROOF_MAX_BYTES) {
    throw new ProofPickError('too_large', 'O arquivo deve ter no máximo 5MB.');
  }

  const mimeType: ProofFile['mimeType'] =
    asset.mimeType === 'image/png' ? 'image/png' : asset.mimeType?.startsWith('image/') ? 'image/jpeg' : 'application/pdf';
  const fallbackName = mimeType === 'application/pdf' ? 'comprovante.pdf' : mimeType === 'image/png' ? 'comprovante.png' : 'comprovante.jpg';

  return { uri: asset.uri, name: asset.name || fallbackName, mimeType };
}

async function shrink(asset: { uri: string; width?: number; height?: number }): Promise<string> {
  const width = asset.width ?? 0;
  const height = asset.height ?? 0;
  const context = ImageManipulator.manipulate(asset.uri);
  if (Math.max(width, height) > PROOF_MAX_SIDE) {
    context.resize(width >= height ? { width: PROOF_MAX_SIDE, height: null } : { width: null, height: PROOF_MAX_SIDE });
  }
  const image = await context.renderAsync();
  try {
    const saved = await image.saveAsync({ compress: 0.8, format: SaveFormat.JPEG });
    return saved.uri;
  } finally {
    image.release();
    context.release();
  }
}
