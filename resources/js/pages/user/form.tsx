import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DialogFooter } from '@/components/ui/dialog';

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
        store_id?: number;
    };
}

export default function Form({ close, stores, initialData }: Props) {
    const isEdit = !!initialData?.id;

    const { data, setData, post, put, processing, errors, reset } = useForm({
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
            put(route('user.update', initialData.id), options);
        } else {
            post(route('user.store'), options);
        }
    };

    const isValid =
        data.name.trim() !== '' &&
        data.username.trim() !== '' &&
        data.email.trim() !== '' &&
        data.store_id !== '';

    return (
        <form onSubmit={submit} className="mt-4 space-y-4">
            {/* NAMA */}
            <div className="grid grid-cols-3 items-center gap-3">
                <Label>Nama*</Label>

                <div className="col-span-2">
                    <Input
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Nama user"
                    />

                    {errors.name && (
                        <p className="text-sm text-red-500">{errors.name}</p>
                    )}
                </div>
            </div>

            {/* USERNAME */}
            <div className="grid grid-cols-3 items-center gap-3">
                <Label>Username*</Label>

                <div className="col-span-2">
                    <Input
                        value={data.username}
                        onChange={(e) => setData('username', e.target.value)}
                        placeholder="Username"
                    />

                    {errors.username && (
                        <p className="text-sm text-red-500">
                            {errors.username}
                        </p>
                    )}
                </div>
            </div>

            {/* EMAIL */}
            <div className="grid grid-cols-3 items-center gap-3">
                <Label>Email*</Label>

                <div className="col-span-2">
                    <Input
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="Email"
                    />

                    {errors.email && (
                        <p className="text-sm text-red-500">{errors.email}</p>
                    )}
                </div>
            </div>

            {/* STORE */}
            <div className="grid grid-cols-3 items-center gap-3">
                <Label>Store*</Label>

                <div className="col-span-2">
                    <select
                        value={data.store_id}
                        onChange={(e) =>
                            setData(
                                'store_id',
                                e.target.value ? Number(e.target.value) : '',
                            )
                        }
                        className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">Pilih Store</option>

                        {stores.map((store) => (
                            <option key={store.id} value={store.id}>
                                {store.name}
                            </option>
                        ))}
                    </select>

                    {errors.store_id && (
                        <p className="text-sm text-red-500">
                            {errors.store_id}
                        </p>
                    )}
                </div>
            </div>

            {/* PASSWORD */}
            <div className="grid grid-cols-3 items-center gap-3">
                <div className="flex flex-col gap-1">
                    <Label>Password</Label>

                    {isEdit && (
                        <Label className="text-xs text-[#919191]">
                            Kosongkan jika tidak diubah
                        </Label>
                    )}
                </div>

                <div className="col-span-2">
                    <Input
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder={isEdit ? 'Password baru' : 'Password'}
                    />

                    {errors.password && (
                        <p className="text-sm text-red-500">
                            {errors.password}
                        </p>
                    )}
                </div>
            </div>

            {/* CONFIRM PASSWORD */}
            <div className="grid grid-cols-3 items-center gap-3">
                <Label>Konfirmasi</Label>

                <div className="col-span-2">
                    <Input
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        placeholder="Konfirmasi password"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    onClick={close}
                    disabled={processing}
                >
                    Batal
                </Button>

                <Button type="submit" disabled={processing || !isValid}>
                    {processing ? 'Menyimpan...' : 'Simpan'}
                </Button>
            </DialogFooter>
        </form>
    );
}
