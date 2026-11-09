import { EmptyState } from '@/components/ui/empty-state';
import { Button } from '@/components/ui/button';
import { errorMessage } from '@/services/api';

/** O que a tela mostra quando não conseguiu buscar os dados. */
export function LoadError({ error, onRetry }: { error: unknown; onRetry: () => void }) {
  return (
    <EmptyState icon="cloud-offline-outline" title="Não deu para carregar" text={errorMessage(error)}>
      <Button label="Tentar de novo" variant="secondary" icon="refresh" onPress={onRetry} />
    </EmptyState>
  );
}
