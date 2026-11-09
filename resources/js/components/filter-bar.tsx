import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/native-select';
import { cleanQuery } from '@/lib/format';
import type { Option } from '@/types/domain';

export type FilterField =
    | { name: string; label: string; type: 'date' }
    | { name: string; label: string; type: 'select'; options: Option[] };

export function FilterBar({
    path,
    fields,
    initial,
    extra,
}: {
    path: string;
    fields: FilterField[];
    initial: Record<string, string | undefined>;
    extra?: ReactNode;
}) {
    const [values, setValues] =
        useState<Record<string, string | undefined>>(initial);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(path, cleanQuery(values), { preserveState: true });
    };

    return (
        <form
            onSubmit={submit}
            className="flex flex-wrap items-end gap-3 rounded-md border bg-card p-3"
        >
            {fields.map((field) => (
                <div key={field.name} className="grid min-w-40 gap-1">
                    <label className="text-xs text-muted-foreground">
                        {field.label}
                    </label>
                    {field.type === 'date' ? (
                        <Input
                            type="date"
                            value={values[field.name] ?? ''}
                            onChange={(e) =>
                                setValues({
                                    ...values,
                                    [field.name]: e.target.value,
                                })
                            }
                        />
                    ) : (
                        <NativeSelect
                            value={values[field.name] ?? ''}
                            onChange={(e) =>
                                setValues({
                                    ...values,
                                    [field.name]: e.target.value,
                                })
                            }
                        >
                            <option value="">Semua</option>
                            {field.options.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.plat ?? option.nama}
                                </option>
                            ))}
                        </NativeSelect>
                    )}
                </div>
            ))}
            <Button type="submit">Terapkan</Button>
            <Button
                type="button"
                variant="outline"
                onClick={() => {
                    setValues({});
                    router.get(path, {}, { preserveState: true });
                }}
            >
                Reset
            </Button>
            {extra}
        </form>
    );
}
