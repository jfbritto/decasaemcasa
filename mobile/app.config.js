// Estende o app.json (o Expo passa o conteúdo dele em `config`) com o que vem
// do ambiente e não pode ficar fixo nele, como no HelpCheck:
// - o google-services.json (push no Android pelo FCM), só quando existe;
// - o projectId do EAS e a URL das atualizações OTA, que o `eas init` cria
//   (EAS_PROJECT_ID). Sem ele o app funciona, só não recebe push nem OTA.
// Ver docs/infra/publicacao-lojas.md.
const fs = require('fs');
const path = require('path');

module.exports = ({ config }) => {
  const googleServicesFile =
    process.env.GOOGLE_SERVICES_JSON ||
    (fs.existsSync(path.join(__dirname, 'google-services.json')) ? './google-services.json' : undefined);

  const projectId = process.env.EAS_PROJECT_ID || config.extra?.eas?.projectId;

  return {
    ...config,
    android: { ...config.android, ...(googleServicesFile ? { googleServicesFile } : {}) },
    ...(projectId ? { updates: { ...config.updates, url: `https://u.expo.dev/${projectId}` } } : {}),
    extra: {
      ...config.extra,
      ...(projectId ? { eas: { ...config.extra?.eas, projectId } } : {}),
    },
  };
};
