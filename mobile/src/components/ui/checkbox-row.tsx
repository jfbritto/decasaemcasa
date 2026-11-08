import { Ionicons } from '@react-native-vector-icons/ionicons';
import { Pressable, StyleSheet, View } from 'react-native';

import { AppText } from '@/components/ui/app-text';
import { brand, colors } from '@/theme/colors';
import { radius, spacing } from '@/theme/spacing';

type Props = {
  checked: boolean;
  onToggle: () => void;
  label: string;
  testID?: string;
};

export function CheckboxRow({ checked, onToggle, label, testID }: Props) {
  return (
    <Pressable
      testID={testID}
      accessibilityRole="checkbox"
      accessibilityState={{ checked }}
      accessibilityLabel={label}
      onPress={onToggle}
      style={[styles.row, { borderColor: checked ? brand[600] : colors.control, backgroundColor: checked ? brand[50] : colors.surface }]}
    >
      <View style={[styles.box, { borderColor: checked ? brand[600] : colors.control, backgroundColor: checked ? brand[600] : colors.surface }]}>
        {checked ? <Ionicons name="checkmark" size={18} color={colors.onPrimary} /> : null}
      </View>
      <AppText variant="body" style={styles.label}>
        {label}
      </AppText>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.lg,
    borderRadius: radius.lg,
    borderWidth: 1.5,
  },
  box: {
    width: 26,
    height: 26,
    borderRadius: 8,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 1,
  },
  label: {
    flex: 1,
  },
});
