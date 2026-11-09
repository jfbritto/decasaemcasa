// Um fetch falso por rota, para os testes do cliente HTTP e da sessão.

type Answer = { status: number; body?: unknown };
type Handler = (init: RequestInit & { url: string }) => Answer | Promise<Answer>;

export type FetchCall = { url: string; method: string; headers: Record<string, string>; body: unknown };

export function mockFetch(routes: Record<string, Handler | Answer>) {
  const calls: FetchCall[] = [];
  const fetchMock = jest.fn(async (url: string, init: RequestInit = {}) => {
    const method = (init.method ?? 'GET').toUpperCase();
    const path = url.replace(/^.*\/api\/v1/, '');
    calls.push({
      url,
      method,
      headers: (init.headers ?? {}) as Record<string, string>,
      body: typeof init.body === 'string' ? JSON.parse(init.body) : init.body,
    });
    const route = routes[`${method} ${path}`];
    if (!route) throw new TypeError(`Network request failed (sem rota falsa para ${method} ${path})`);
    const answer = typeof route === 'function' ? await route({ ...init, url }) : route;
    const text = answer.body === undefined ? '' : JSON.stringify(answer.body);
    return {
      status: answer.status,
      ok: answer.status >= 200 && answer.status < 300,
      text: async () => text,
    } as Response;
  });
  globalThis.fetch = fetchMock as unknown as typeof fetch;
  return { fetchMock, calls };
}
