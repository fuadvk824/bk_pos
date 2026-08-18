import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import {
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useRoute } from '@/lib/route-ziggy';

interface StoreOption {
    id: number;
    name: string;
}

interface Props {
    close: () => void;
    stores: StoreOption[];
    initialData?: {
        id?: number;
        name: string;
        username?: string;
        email: string;
        store_id?: number | null;
    };
}

export default function Form({
    close,
    stores,
    initialData,
}: Props) {
    const route = useRoute();
    const isEdit = !!initialData?.id;

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
    } = useForm<{
        name: string;
        username: string;
        email: string;
        store_id: number | '';
        password: string;
        password_confirmation: string;
    }>({
        name: initialData?.name ?? '',
        username: initialData?.username ?? '',
        email: initialData?.email ?? '',
        store_id: initialData?.store_id ?? '',
        password: '',
        password_confirmation: '',
    });

    const handleToast = (page: any) => {
        const flash = page.props.flash as {
            success?: string;
            error?: string;
        };

        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,

            onSuccess: (page: any) => {
                handleToast(page);
                close();
                reset();
            },
        };

        if (isEdit && initialData?.id) {
            put(
                route('user.update', {
                    user: initialData.id,
                }),
                options,
            );
        } else {
            post(route('user.store'), options);
        }
    };

    const isValid =
        data.name.trim() !== '' &&
        data.username.trim() !== '' &&
        data.email.trim() !== '' &&
        data.store_id !== '' &&
        (!isEdit
            ? data.password.trim() !== '' &&
              data.password_confirmation.trim() !== ''
            : true);

    return (
        <form
            onSubmit={submit}
            className="space-y-5"
        >
            <div>
                <Label htmlFor="name">
                    Nama <span className="text-destructive">*</span>
                </Label>

                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) =>
                        setData('name', e.target.value)
                    }
                    placeholder="Masukkan nama user"
                    aria-invalid={!!errors.name}
                />

                {errors.name && (
                    <p className="text-sm text-destructive">
                        {errors.name}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="username">
                    Username{' '}
                    <span className="text-destructive">*</span>
                </Label>

                <Input
                    id="username"
                    value={data.username}
                    onChange={(e) =>
                        setData('username', e.target.value)
                    }
                    placeholder="Masukkan username"
                    aria-invalid={!!errors.username}
                />

                {errors.username && (
                    <p className="text-sm text-destructive">
                        {errors.username}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="email">
                    Email <span className="text-destructive">*</span>
                </Label>

                <Input
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) =>
                        setData('email', e.target.value)
                    }
                    placeholder="contoh@email.com"
                    aria-invalid={!!errors.email}
                />

                {errors.email && (
                    <p className="text-sm text-destructive">
                        {errors.email}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="store">
                    Store <span className="text-destructive">*</span>
                </Label>

                <Select
                    value={
                        data.store_id !== ''
                            ? String(data.store_id)
                            : undefined
                    }
                    onValueChange={(value) =>
                        setData('store_id', Number(value))
                    }
                >
                    <SelectTrigger
                        id="store"
                        className={
                            errors.store_id
                                ? 'border-destructive'
                                : ''
                        }
                    >
                        <SelectValue placeholder="Pilih store" />
                    </SelectTrigger>

                    <SelectContent align='start'>
                        {stores.map((store) => (
                            <SelectItem
                                key={store.id}
                                value={String(store.id)}
                            >
                                {store.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {errors.store_id && (
                    <p className="text-sm text-destructive">
                        {errors.store_id}
                    </p>
                )}
            </div>

            <div>
                <div>
                    <Label htmlFor="password">
                        Password
                    </Label>

                    {isEdit && (
                        <p className="mt-1 text-xs text-muted-foreground">
                            Kosongkan jika password tidak ingin
                            diubah.
                        </p>
                    )}
                </div>

                <Input
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={(e) =>
                        setData('password', e.target.value)
                    }
                    placeholder={
                        isEdit
                            ? 'Masukkan password baru'
                            : 'Masukkan password'
                    }
                    aria-invalid={!!errors.password}
                />

                {errors.password && (
                    <p className="text-sm text-destructive">
                        {errors.password}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor="password_confirmation">
                    Konfirmasi Password
                </Label>

                <Input
                    id="password_confirmation"
                    type="password"
                    value={data.password_confirmation}
                    onChange={(e) =>
                        setData(
                            'password_confirmation',
                            e.target.value,
                        )
                    }
                    placeholder="Ulangi password"
                    aria-invalid={
                        !!errors.password_confirmation
                    }
                />

                {errors.password_confirmation && (
                    <p className="text-sm text-destructive">
                        {errors.password_confirmation}
                    </p>
                )}
            </div>

            <DialogFooter className="pt-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={close}
                    disabled={processing}
                >
                    Batal
                </Button>

                <Button
                    type="submit"
                    disabled={processing || !isValid}
                >
                    {processing
                        ? 'Menyimpan...'
                        : isEdit
                          ? 'Perbarui'
                          : 'Simpan'}
                </Button>
            </DialogFooter>
        </form>
    );
}