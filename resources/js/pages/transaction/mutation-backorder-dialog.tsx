import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import { Button } from '@/components/ui/button';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, ArrowRight, Package } from 'lucide-react';
import { useRoute } from '@/lib/route-ziggy';
import { TransactionDetail } from '@/types/custom/mutasi-transaction';

interface MutationBackorderDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    transaction: TransactionDetail;
}

interface MutationSelection {
    transaction_item_id: number;
    source_store_id: number | null;
    quantity: number;
}

export default function MutationBackorderDialog({
    open,
    onOpenChange,
    transaction,
}: MutationBackorderDialogProps) {
    const route = useRoute();

    const [selections, setSelections] = useState<MutationSelection[]>([]);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const waitingStockItems = useMemo(() => {
        return transaction.items.filter(
            (item) => item.fulfillment_status === 'waiting_stock',
        );
    }, [transaction.items]);

    const selectionMap = useMemo(() => {
        const map = new Map<number, MutationSelection>();

        for (const selection of selections) {
            map.set(selection.transaction_item_id, selection);
        }

        return map;
    }, [selections]);

    const availableStoresMap = useMemo(() => {
        const map = new Map<
            number,
            (typeof transaction.items)[number]['stores']
        >();

        for (const item of waitingStockItems) {
            const stores = item.stores.filter(
                (store) =>
                    store.id !== transaction.store_id &&
                    Number(store.stock) > 0,
            );

            map.set(item.id, stores);
        }

        return map;
    }, [waitingStockItems, transaction.store_id]);

    const handleSourceChange = (
        transactionItemId: number,
        sourceStoreId: string,
    ) => {
        const storeId = Number(sourceStoreId);

        if (!Number.isFinite(storeId)) {
            return;
        }

        const item = waitingStockItems.find(
            (item) => item.id === transactionItemId,
        );

        if (!item) {
            return;
        }

        const availableStores = availableStoresMap.get(transactionItemId) ?? [];

        const selectedStore = availableStores.find(
            (store) => Number(store.id) === storeId,
        );

        if (!selectedStore) {
            return;
        }

        const sourceStock = Math.max(0, Number(selectedStore.stock));

        setSelections((current) => {
            const existingIndex = current.findIndex(
                (selection) =>
                    selection.transaction_item_id === transactionItemId,
            );

            if (existingIndex !== -1) {
                const updated = [...current];

                const currentQuantity = updated[existingIndex].quantity;

                const quantity =
                    currentQuantity > sourceStock
                        ? sourceStock
                        : currentQuantity;

                updated[existingIndex] = {
                    ...updated[existingIndex],
                    source_store_id: storeId,
                    quantity,
                };

                return updated;
            }

            return [
                ...current,
                {
                    transaction_item_id: transactionItemId,
                    source_store_id: storeId,
                    quantity: 0,
                },
            ];
        });

        setError(null);
    };

    const handleQuantityChange = (transactionItemId: number, value: string) => {
       
        if (value === '') {
            setSelections((current) => {
                const existingIndex = current.findIndex(
                    (selection) =>
                        selection.transaction_item_id === transactionItemId,
                );

                if (existingIndex !== -1) {
                    const updated = [...current];

                    updated[existingIndex] = {
                        ...updated[existingIndex],
                        quantity: 0,
                    };

                    return updated;
                }

                return [
                    ...current,
                    {
                        transaction_item_id: transactionItemId,
                        source_store_id: null,
                        quantity: 0,
                    },
                ];
            });

            setError(null);

            return;
        }

        const quantity = Number(value);

        if (!Number.isFinite(quantity) || quantity < 0) {
            return;
        }

        const item = waitingStockItems.find(
            (item) => item.id === transactionItemId,
        );

        if (!item) {
            return;
        }

        const selection = selectionMap.get(transactionItemId);

        let maxQuantity = Infinity;

        if (
            selection?.source_store_id !== null &&
            selection?.source_store_id !== undefined
        ) {
            const availableStores =
                availableStoresMap.get(transactionItemId) ?? [];

            const selectedStore = availableStores.find(
                (store) =>
                    Number(store.id) === Number(selection.source_store_id),
            );

            if (selectedStore) {
                maxQuantity = Math.max(0, Number(selectedStore.stock));
            }
        }

        if (
            selection?.source_store_id === null ||
            selection?.source_store_id === undefined
        ) {
            if (quantity > 0) {
                setError(
                    `Pilih source store terlebih dahulu untuk produk "${item.product_name}".`,
                );
            }

            return;
        }

        if (quantity > maxQuantity) {
            setSelections((current) => {
                const existingIndex = current.findIndex(
                    (selection) =>
                        selection.transaction_item_id === transactionItemId,
                );

                if (existingIndex === -1) {
                    return current;
                }

                const updated = [...current];

                updated[existingIndex] = {
                    ...updated[existingIndex],
                    quantity: maxQuantity,
                };

                return updated;
            });

            setError(
                `Quantity mutasi produk "${item.product_name}" tidak boleh melebihi stock source (${maxQuantity} unit).`,
            );

            return;
        }

        setSelections((current) => {
            const existingIndex = current.findIndex(
                (selection) =>
                    selection.transaction_item_id === transactionItemId,
            );

            if (existingIndex !== -1) {
                const updated = [...current];

                updated[existingIndex] = {
                    ...updated[existingIndex],
                    quantity,
                };

                return updated;
            }

            return [
                ...current,
                {
                    transaction_item_id: transactionItemId,
                    source_store_id: null,
                    quantity,
                },
            ];
        });

        setError(null);
    };

    const invalidItem = useMemo(() => {
        return waitingStockItems.find((item) => {
            const selection = selectionMap.get(item.id);

            if (
                selection?.source_store_id === null ||
                selection?.source_store_id === undefined
            ) {
                return true;
            }

            if (!selection || selection.quantity <= 0) {
                return true;
            }

            const availableStores = availableStoresMap.get(item.id) ?? [];

            const sourceStore = availableStores.find(
                (store) =>
                    Number(store.id) === Number(selection.source_store_id),
            );

            if (!sourceStore) {
                return true;
            }

            if (selection.quantity > Number(sourceStore.stock)) {
                return true;
            }

            return false;
        });
    }, [waitingStockItems, selectionMap, availableStoresMap]);

    const canSubmit =
        waitingStockItems.length > 0 && !invalidItem && !processing;

    const handleSubmit = () => {
        setError(null);

        if (waitingStockItems.length === 0) {
            setError('Tidak ada produk yang perlu dimutasi.');

            return;
        }

        for (const item of waitingStockItems) {
            const selection = selectionMap.get(item.id);

            if (
                selection?.source_store_id === null ||
                selection?.source_store_id === undefined
            ) {
                setError(
                    `Silakan pilih source store untuk produk "${item.product_name}".`,
                );

                return;
            }

            const availableStores = availableStoresMap.get(item.id) ?? [];

            const sourceStore = availableStores.find(
                (store) =>
                    Number(store.id) === Number(selection.source_store_id),
            );

            if (!sourceStore) {
                setError(
                    `Source store untuk produk "${item.product_name}" tidak tersedia.`,
                );

                return;
            }

            if (selection.quantity <= 0) {
                setError(
                    `Quantity mutasi untuk produk "${item.product_name}" harus lebih dari 0.`,
                );

                return;
            }

            const sourceStock = Number(sourceStore.stock);

            if (selection.quantity > sourceStock) {
                setError(
                    `Quantity mutasi produk "${item.product_name}" tidak boleh melebihi stock source (${sourceStock} unit).`,
                );

                return;
            }
        }

        const payload = {
            items: waitingStockItems.map((item) => {
                const selection = selectionMap.get(item.id)!;

                return {
                    transaction_item_id: item.id,

                    source_store_id: selection.source_store_id,

                    quantity: selection.quantity,
                };
            }),
        };

        setProcessing(true);

        router.post(
            route('transaction.backorder.mutation', transaction.id),
            payload,
            {
                preserveScroll: true,

                onSuccess: () => {
                    setSelections([]);
                    setError(null);
                    onOpenChange(false);
                },

                onError: (errors) => {
                    const firstError = Object.values(errors)[0];

                    if (typeof firstError === 'string') {
                        setError(firstError);
                    } else {
                        setError(
                            'Mutasi gagal diproses. Silakan periksa kembali data mutasi.',
                        );
                    }
                },

                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    };

    const handleOpenChange = (value: boolean) => {
        if (processing) {
            return;
        }

        if (!value) {
            setSelections([]);
            setError(null);
        }

        onOpenChange(value);
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-hidden sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Mutasi Backorder</DialogTitle>

                    <DialogDescription>
                        Pilih toko asal dan tentukan jumlah stock yang ingin
                        dipindahkan.
                    </DialogDescription>
                </DialogHeader>

                <div className="rounded-lg border bg-muted/30 p-4">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Invoice
                            </p>

                            <p className="font-semibold">
                                {transaction.invoice_number}
                            </p>
                        </div>

                        <div className="text-right">
                            <p className="text-xs text-muted-foreground">
                                Toko Tujuan
                            </p>

                            <p className="font-semibold">
                                {transaction.store?.name}
                            </p>
                        </div>
                    </div>
                </div>

                {error && (
                    <Alert variant="destructive">
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                <div className="min-h-0 flex-1 overflow-y-auto pr-1">
                    {waitingStockItems.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-10 text-center">
                            <Package className="mb-3 h-10 w-10 text-muted-foreground" />

                            <p className="font-medium">Tidak ada backorder</p>

                            <p className="text-sm text-muted-foreground">
                                Semua produk sudah terpenuhi.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {waitingStockItems.map((item, index) => {
                                const selection = selectionMap.get(item.id);

                                const availableStores =
                                    availableStoresMap.get(item.id) ?? [];

                                const sourceValue =
                                    selection?.source_store_id !== null &&
                                    selection?.source_store_id !== undefined
                                        ? String(selection.source_store_id)
                                        : '';

                                const quantityValue =
                                    selection?.quantity &&
                                    selection.quantity > 0
                                        ? String(selection.quantity)
                                        : '';

                                const selectedSource = availableStores.find(
                                    (store) =>
                                        Number(store.id) ===
                                        Number(selection?.source_store_id),
                                );

                                const sourceStock = selectedSource
                                    ? Number(selectedSource.stock)
                                    : 0;

                                return (
                                    <div key={item.id}>
                                        {index > 0 && (
                                            <Separator className="mb-4" />
                                        )}

                                        <div className="grid gap-4 md:grid-cols-[1fr_300px]">
                                            <div className="min-w-0">
                                                <div className="flex items-start gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-muted">
                                                        <Package className="h-5 w-5 text-muted-foreground" />
                                                    </div>

                                                    <div className="min-w-0">
                                                        <p className="text-sm font-medium">
                                                            {item.product_name}
                                                        </p>
                                                        <p className="text-xs">
                                                            (Stok:{' '}
                                                            {
                                                                item.stock_at_transaction
                                                            }
                                                            )
                                                        </p>

                                                        <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                                            <span>
                                                                Kebutuhan
                                                                transaksi:{' '}
                                                                <strong className="text-foreground">
                                                                    {
                                                                        item.quantity
                                                                    }
                                                                </strong>
                                                            </span>

                                                            <span>
                                                                Status:{' '}
                                                                <strong className="text-yellow-600">
                                                                    Waiting
                                                                    Stock
                                                                </strong>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="space-y-3">
                                                <div>
                                                    <p className="mb-1 text-xs font-medium">
                                                        Toko Asal
                                                    </p>

                                                    <Select
                                                        value={sourceValue}
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            handleSourceChange(
                                                                item.id,
                                                                value,
                                                            )
                                                        }
                                                        disabled={processing}
                                                    >
                                                        <SelectTrigger className="w-full">
                                                            <SelectValue
                                                                placeholder="Pilih source store"
                                                                className="text-[10px]"
                                                            />
                                                        </SelectTrigger>

                                                        <SelectContent>
                                                            {availableStores.length ===
                                                            0 ? (
                                                                <SelectItem
                                                                    value="no-stock"
                                                                    disabled
                                                                >
                                                                    Tidak ada
                                                                    source store
                                                                </SelectItem>
                                                            ) : (
                                                                availableStores.map(
                                                                    (store) => (
                                                                        <SelectItem
                                                                            key={
                                                                                store.id
                                                                            }
                                                                            value={String(
                                                                                store.id,
                                                                            )}
                                                                        >
                                                                            <span>
                                                                                {
                                                                                    store.name
                                                                                }
                                                                            </span>
                                                                        </SelectItem>
                                                                    ),
                                                                )
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </div>

                                                <div>
                                                    <p className="mb-1 text-xs font-medium">
                                                        Jumlah Mutasi
                                                    </p>

                                                    <Input
                                                        type="number"
                                                        min={1}
                                                        max={
                                                            selectedSource
                                                                ? sourceStock
                                                                : undefined
                                                        }
                                                        step={1}
                                                        value={quantityValue}
                                                        onChange={(e) =>
                                                            handleQuantityChange(
                                                                item.id,
                                                                e.target.value,
                                                            )
                                                        }
                                                        disabled={
                                                            processing ||
                                                            !selectedSource
                                                        }
                                                        placeholder={
                                                            selectedSource
                                                                ? `Maks. ${sourceStock}`
                                                                : 'Pilih source store'
                                                        }
                                                    />

                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {selectedSource
                                                            ? `Stock toko asal: ${sourceStock} unit`
                                                            : 'Pilih source store terlebih dahulu'}
                                                    </p>

                                                    {selection?.source_store_id !==
                                                        null &&
                                                        selection?.source_store_id !==
                                                            undefined &&
                                                        selection.quantity >
                                                            0 && (
                                                            <p className="mt-1 text-xs text-muted-foreground">
                                                                Akan
                                                                dipindahkan:{' '}
                                                                <strong>
                                                                    {
                                                                        selection.quantity
                                                                    }
                                                                </strong>{' '}
                                                                unit
                                                            </p>
                                                        )}

                                                    {selection &&
                                                        selection.quantity >
                                                            item.quantity && (
                                                            <p className="mt-1 text-xs text-blue-600">
                                                                Kebutuhan
                                                                transaksi:{' '}
                                                                {item.quantity}{' '}
                                                                unit. Kelebihan
                                                                stock akan
                                                                menjadi stock
                                                                toko tujuan.
                                                            </p>
                                                        )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                <DialogFooter className="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => handleOpenChange(false)}
                        disabled={processing}
                    >
                        Batal
                    </Button>

                    <Button
                        type="button"
                        onClick={handleSubmit}
                        disabled={!canSubmit}
                    >
                        {processing ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Memproses...
                            </>
                        ) : (
                            <>
                                <ArrowRight className="mr-2 h-4 w-4" />
                                Proses Mutasi
                            </>
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
