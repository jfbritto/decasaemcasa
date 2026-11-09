import { useEffect, useRef, useState } from 'react';

import { AppText } from '@/components/ui/app-text';
import { formatRemaining } from '@/lib/format';
import { tones } from '@/theme/colors';

/**
 * Quanto falta para o prazo do comprovante (spec
 * 2026-11-08-app-mobile-design, AM31), de segundo em segundo. Ao zerar
 * chama `onExpire` uma vez: quem decide a fila de espera é o cron do
 * servidor, não o app.
 */
export function Countdown({ deadline, onExpire }: { deadline: string; onExpire?: () => void }) {
  const target = Date.parse(deadline);
  const [now, setNow] = useState(() => Date.now());
  const expiredRef = useRef(false);

  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(timer);
  }, []);

  const remaining = formatRemaining(target - now);

  useEffect(() => {
    if (remaining === null && !expiredRef.current) {
      expiredRef.current = true;
      onExpire?.();
    }
  }, [remaining, onExpire]);

  if (remaining === null) {
    return (
      <AppText variant="body" color={tones.waiting.text} testID="countdown-ended">
        O prazo terminou. Se você não enviou o comprovante, sua inscrição voltará para a fila de espera em instantes.
      </AppText>
    );
  }

  return (
    <AppText variant="body" color={tones.waiting.text} testID="countdown">
      {'Tempo restante: '}
      <AppText variant="bodyStrong" color={tones.waiting.text}>
        {remaining}
      </AppText>
    </AppText>
  );
}
