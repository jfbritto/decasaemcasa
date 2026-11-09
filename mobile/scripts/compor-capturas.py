"""Monta as imagens das lojas a partir das capturas reais do app (store/originais).

Mesmo método do HelpCheck e do TreinaEdu: a tela REAL do app, gravada contra o banco
fictício "Velunora", ganha o fundo terracota da marca e uma frase curta em cima.
Saem os tamanhos que as lojas pedem:

- App Store, iPhone com Dynamic Island, tela média (1206 x 2622): o quadro que o App Store
  Connect pede primeiro desde 2026.
- App Store, iPhone 6,9" (1320 x 2868).
- App Store, cabeçalho da página do produto e resultados de busca (16:9, 5244 x 2950).
- Google Play, telefone (1080 x 1920, 9:16) e recurso gráfico (1024 x 500).

As telas da App Store saem das capturas do simulador do iPhone (originais/ios) e as do
Google Play, das do emulador Android (originais/android): a Apple recusa imagem com
interface de outra plataforma. Tudo em PNG sem transparência.

Rodar da pasta mobile/ (Python 3 com Pillow):  python3 scripts/compor-capturas.py
Como tirar as capturas de novo: store/textos.md, seção 3.
"""
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

MOBILE = Path(__file__).resolve().parent.parent
STORE = MOBILE / "store"
ORIGINAIS = STORE / "originais"
# Fraunces e Figtree, as fontes do app (OFL), dos pacotes do @expo-google-fonts.
FONTES = MOBILE / "node_modules" / "@expo-google-fonts"
TITULO = FONTES / "fraunces" / "700Bold" / "Fraunces_700Bold.ttf"
TEXTO = FONTES / "figtree" / "500Medium" / "Figtree_500Medium.ttf"

# O terracota do app (src/theme/colors.ts, brand 600), escurecido no fim (brand 800) para a
# frase branca ler bem em qualquer altura.
TERRACOTA_TOPO = (0xC2, 0x62, 0x1B)
TERRACOTA_BASE = (0x7A, 0x3A, 0x10)
AREIA = (0xFB, 0xE9, 0xD7)
BRILHO = (0xF5, 0xCF, 0xA8)
BRANCO = (255, 255, 255)

# A ordem das capturas na loja e a frase de cada uma. Cada frase diz o que a TELA mostra:
# nada que o app não faça. Sem travessão; "seu lugar na sala", nunca "cadeira".
FRASES = {
    "01-encontros": "Os encontros da turnê no seu celular",
    "02-encontro": "Saiba como funciona cada encontro",
    "03-inscricao": "Conte sua história na inscrição",
    "04-aprovada": "O prazo e o Pix na mesma tela",
    "05-confirmada": "Seu lugar na sala, com endereço e horário",
    "06-avisos": "Os avisos da equipe chegam na hora",
    "07-inscricoes": "O status de cada inscrição à mão",
}

DESTAQUE = ("De Casa em Casa", "Inscrição, prazo e avisos", "da turnê no seu celular")

# O cabeçalho da App Store: a tela do meio e as dos lados.
CABECALHO_CENTRO = "01-encontros"
CABECALHO_LADOS = ("02-encontro", "05-confirmada")

TAMANHO_IOS = (1320, 2868)
TAMANHO_ANDROID = (1080, 2340)


def fundo(largura, altura):
    """O terracota da marca, num degradê de cima para baixo."""
    img = Image.new("RGB", (largura, altura), TERRACOTA_TOPO)
    d = ImageDraw.Draw(img)
    for y in range(altura):
        t = y / max(altura - 1, 1)
        cor = tuple(round(TERRACOTA_TOPO[i] + (TERRACOTA_BASE[i] - TERRACOTA_TOPO[i]) * t) for i in range(3))
        d.line([(0, y), (largura, y)], fill=cor)
    return img


def quebrar(texto, fonte, largura_maxima, d):
    """Quebra a frase em linhas que cabem na largura, equilibrando as duas linhas."""
    palavras = texto.split()
    if d.textlength(texto, font=fonte) <= largura_maxima:
        return [texto]
    melhor = None
    for i in range(1, len(palavras)):
        a, b = " ".join(palavras[:i]), " ".join(palavras[i:])
        la, lb = d.textlength(a, font=fonte), d.textlength(b, font=fonte)
        if max(la, lb) <= largura_maxima and (melhor is None or abs(la - lb) < melhor[0]):
            melhor = (abs(la - lb), [a, b])
    if melhor is None:
        raise SystemExit(f'frase longa demais para duas linhas: "{texto}"')
    return melhor[1]


def escrever_centralizado(img, linhas, fonte, centro_y, entrelinha, cor=BRANCO):
    d = ImageDraw.Draw(img)
    altura_total = entrelinha * len(linhas)
    y = centro_y - altura_total / 2
    for linha in linhas:
        largura = d.textlength(linha, font=fonte)
        d.text(((img.width - largura) / 2, y), linha, font=fonte, fill=cor)
        y += entrelinha


def tela_arredondada(captura, largura, raio):
    """A captura reduzida, com cantos arredondados, borda fina e sombra."""
    altura = round(captura.height * largura / captura.width)
    tela = captura.resize((largura, altura), Image.LANCZOS).convert("RGBA")
    mascara = Image.new("L", (largura, altura), 0)
    ImageDraw.Draw(mascara).rounded_rectangle([0, 0, largura - 1, altura - 1], radius=raio, fill=255)
    tela.putalpha(mascara)
    margem = raio
    com_sombra = Image.new("RGBA", (largura + 2 * margem, altura + 2 * margem), (0, 0, 0, 0))
    sombra = Image.new("L", com_sombra.size, 0)
    ImageDraw.Draw(sombra).rounded_rectangle([margem, margem + 12, margem + largura, margem + altura + 12], radius=raio, fill=150)
    sombra = sombra.filter(ImageFilter.GaussianBlur(margem / 2))
    # Sombra num marrom bem escuro, não preto: fica quente como o fundo.
    com_sombra.paste((40, 16, 0, 255), (0, 0), sombra)
    com_sombra.alpha_composite(tela, (margem, margem))
    ImageDraw.Draw(com_sombra).rounded_rectangle(
        [margem, margem, margem + largura - 1, margem + altura - 1], radius=raio, outline=(255, 255, 255, 70), width=3
    )
    return com_sombra, margem


def compor(captura, frase, largura, altura, largura_da_tela, tamanho_da_letra, area_da_frase, raio):
    img = fundo(largura, altura).convert("RGBA")
    d = ImageDraw.Draw(img)
    fonte = ImageFont.truetype(str(TITULO), tamanho_da_letra)
    linhas = quebrar(frase, fonte, largura * 0.86, d)
    escrever_centralizado(img, linhas, fonte, area_da_frase / 2 + tamanho_da_letra * 0.1, round(tamanho_da_letra * 1.18))
    tela, margem = tela_arredondada(captura, largura_da_tela, raio)
    if area_da_frase + tela.height - 2 * margem > altura:
        raise SystemExit(f"a tela não cabe em {largura} x {altura}")
    x = (largura - tela.width) // 2
    y = area_da_frase - margem
    img.alpha_composite(tela, (x, y))
    return img.convert("RGB")


def destaque(inicio):
    """O recurso gráfico do Google Play: marca à esquerda, a lista de encontros à direita."""
    img = fundo(1024, 500).convert("RGBA")
    d = ImageDraw.Draw(img)
    icone = Image.open(MOBILE / "assets" / "images" / "icon.png").convert("RGBA").resize((132, 132), Image.LANCZOS)
    mascara = Image.new("L", icone.size, 0)
    ImageDraw.Draw(mascara).rounded_rectangle([0, 0, 131, 131], radius=30, fill=255)
    icone.putalpha(mascara)
    img.alpha_composite(icone, (64, 92))
    nome, linha1, linha2 = DESTAQUE
    d.text((64, 250), nome, font=ImageFont.truetype(str(TITULO), 58), fill=BRANCO)
    fonte = ImageFont.truetype(str(TEXTO), 28)
    d.text((66, 334), linha1, font=fonte, fill=AREIA)
    d.text((66, 372), linha2, font=fonte, fill=AREIA)
    tela, margem = tela_arredondada(inicio, 300, 34)
    img.alpha_composite(tela, (650 - margem, 60 - margem))
    return img.convert("RGB")


def cabecalho():
    """O cabeçalho da página do produto na App Store, também usado nos resultados de busca.

    16:9 em 5244 x 2950, sem transparência. A Apple corta as laterais conforme a tela, então o
    que importa fica no centro. Sem texto: o nome e o subtítulo aparecem logo abaixo.
    """
    largura, altura = 5244, 2950
    img = fundo(largura, altura).convert("RGBA")
    brilho = Image.new("L", (largura, altura), 0)
    ImageDraw.Draw(brilho).ellipse([largura / 2 - 1500, 500, largura / 2 + 1500, 2700], fill=80)
    brilho = brilho.filter(ImageFilter.GaussianBlur(400))
    img.paste(Image.new("RGBA", (largura, altura), BRILHO + (255,)), (0, 0), brilho)
    for nome, x in zip(CABECALHO_LADOS, (1022, 3322)):
        tela, margem = tela_arredondada(abrir("ios", nome), 900, 60)
        img.alpha_composite(tela, (x - margem, 820 - margem))
    tela, margem = tela_arredondada(abrir("ios", CABECALHO_CENTRO), 1100, 74)
    img.alpha_composite(tela, ((largura - 1100) // 2 - margem, 420 - margem))
    return img.convert("RGB")


def abrir(plataforma, nome):
    captura = Image.open(ORIGINAIS / plataforma / f"{nome}.png").convert("RGB")
    esperado = TAMANHO_IOS if plataforma == "ios" else TAMANHO_ANDROID
    if captura.size != esperado:
        raise SystemExit(f"{plataforma}/{nome}: {captura.size}; esperado {esperado[0]} x {esperado[1]}")
    return captura


def salvar(img, caminho):
    caminho.parent.mkdir(parents=True, exist_ok=True)
    img.save(caminho, optimize=True)
    print(caminho.relative_to(MOBILE))


def main():
    for nome, frase in FRASES.items():
        ios = abrir("ios", nome)
        salvar(compor(ios, frase, 1320, 2868, 1040, 92, 520, 70), STORE / "app-store" / "capturas-6.9" / f"{nome}.png")
        salvar(compor(ios, frase, 1206, 2622, 950, 84, 475, 64), STORE / "app-store" / "capturas-1206x2622" / f"{nome}.png")
        android = abrir("android", nome)
        salvar(compor(android, frase, 1080, 1920, 680, 64, 370, 46), STORE / "google-play" / "capturas" / f"{nome}.png")
    salvar(destaque(abrir("android", "01-encontros")), STORE / "google-play" / "grafico-recursos-1024x500.png")
    salvar(cabecalho(), STORE / "app-store" / "cabecalho-5244x2950.png")


if __name__ == "__main__":
    main()
