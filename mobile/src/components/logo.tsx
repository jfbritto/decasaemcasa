import Svg, { Circle, Ellipse, Line, Rect } from 'react-native-svg';

/**
 * O violão do De Casa em Casa, o mesmo desenho do public/favicon.svg do site.
 * `tone="light"` desenha em creme, para o fundo terracota.
 */
export function Logo({ size = 48, tone = 'brand' }: { size?: number; tone?: 'brand' | 'light' }) {
  const body = tone === 'light' ? '#FDF5EC' : '#E88A2D';
  const dark = tone === 'light' ? '#F5CFA8' : '#C06A1A';
  const darker = tone === 'light' ? '#EEAF73' : '#B35E17';
  const detail = tone === 'light' ? '#FBE9D7' : '#E88A2D';
  const strings = tone === 'light' ? '#C2621B' : '#FFFFFF';

  return (
    <Svg width={size} height={size} viewBox="0 0 64 64" accessibilityLabel="De Casa em Casa">
      <Rect x={29} y={2} width={6} height={28} rx={2} fill={dark} />
      <Line x1={30} y1={8} x2={34} y2={8} stroke={detail} strokeWidth={1} />
      <Line x1={30} y1={14} x2={34} y2={14} stroke={detail} strokeWidth={1} />
      <Line x1={30} y1={20} x2={34} y2={20} stroke={detail} strokeWidth={1} />
      <Rect x={27} y={0} width={10} height={6} rx={3} fill={darker} />
      <Circle cx={28} cy={2} r={1.5} fill={detail} />
      <Circle cx={36} cy={2} r={1.5} fill={detail} />
      <Circle cx={28} cy={5} r={1.5} fill={detail} />
      <Circle cx={36} cy={5} r={1.5} fill={detail} />
      <Ellipse cx={32} cy={36} rx={14} ry={11} fill={body} />
      <Ellipse cx={32} cy={50} rx={17} ry={13} fill={body} />
      <Ellipse cx={32} cy={43} rx={11} ry={6} fill={body} />
      <Circle cx={32} cy={44} r={6} fill={dark} />
      <Circle cx={32} cy={44} r={5} fill="none" stroke={darker} strokeWidth={1} />
      <Circle cx={32} cy={44} r={7} fill="none" stroke={darker} strokeWidth={0.8} />
      <Rect x={28} y={52} width={8} height={2} rx={1} fill={darker} />
      <Line x1={31} y1={6} x2={31} y2={52} stroke={strings} strokeWidth={0.5} opacity={0.6} />
      <Line x1={33} y1={6} x2={33} y2={52} stroke={strings} strokeWidth={0.5} opacity={0.6} />
      <Line x1={30} y1={6} x2={30} y2={52} stroke={strings} strokeWidth={0.4} opacity={0.4} />
      <Line x1={34} y1={6} x2={34} y2={52} stroke={strings} strokeWidth={0.4} opacity={0.4} />
    </Svg>
  );
}
