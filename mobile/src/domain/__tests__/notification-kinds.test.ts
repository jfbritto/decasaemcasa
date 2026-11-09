import { readFileSync } from 'node:fs';
import { join } from 'node:path';

import { badgeLabel, NOTIFICATION_KINDS, notificationLook } from '@/domain/notification-kinds';

describe('notificationLook', () => {
  it('cada tipo que o servidor grava tem ícone e tom próprios', () => {
    // Os tipos de App\Notifications\Push\ParticipantPush (o 2º argumento do send).
    const source = readFileSync(join(__dirname, '../../../../app/Notifications/Push/ParticipantPush.php'), 'utf8');
    const serverKinds = [...source.matchAll(/->send\(\$inscription, '([a-z_]+)'/g)].map((m) => m[1]).sort();

    expect(serverKinds.length).toBeGreaterThan(10);
    expect([...NOTIFICATION_KINDS].sort()).toEqual(serverKinds);
  });

  it('tipo desconhecido não quebra', () => {
    expect(notificationLook('novo_tipo')).toEqual({ icon: 'notifications-outline', tone: 'neutral' });
  });
});

describe('badgeLabel', () => {
  it.each([
    [0, undefined],
    [1, '1'],
    [9, '9'],
    [10, '9+'],
  ])('%d', (count, label) => {
    expect(badgeLabel(count)).toBe(label);
  });
});
