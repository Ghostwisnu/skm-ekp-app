import { Switch as SwitchPrimitive } from '@base-ui-components/react/switch';
import { cn } from '@/lib/utils';

type SwitchProps = React.ComponentProps<typeof SwitchPrimitive>;

export function Switch({ className, ...props }: SwitchProps) {
    return (
        <SwitchPrimitive
            className={cn(
                'peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors',
                'data-[checked]:bg-primary data-[unchecked]:bg-input',
                'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                className,
            )}
            {...props}
        />
    );
}