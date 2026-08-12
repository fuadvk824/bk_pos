import { useForm } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Transaction } from '@/types/custom/transaction';
import { formatRupiah } from '@/lib/format-rupiah';
import { useRoute } from '@/lib/route-ziggy';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Props {
    open: boolean;
    onOpenChange: (value: boolean) => void;
    transaction: Transaction | null;
}

export default function ProcessTransactionDialog({
    open,
    onOpenChange,
    transaction,
}: Props) {
    const route = useRoute();

    const { data, setData, post, processing } = useForm({
        driver_name: transaction?.driver_name ?? '',
        payment_method: 'cash',
        amount: 0,
    });

    const submit = () => {
        if (!transaction) return;

        post(route('transaction.process', transaction.id), {
            preserveScroll: true,
            onSuccess: () => {
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader className="gap-0">
                    <DialogTitle>Proses Transaksi</DialogTitle>
                    <DialogDescription>
                        Selesaikan proses transaksi yang belum lunas
                    </DialogDescription>
                </DialogHeader>

                {transaction && (
                    <div className="space-y-2">
                        <div className="space-y-1 rounded-md border p-3 text-xs">
                            {[
                                ['Invoice', transaction.invoice_number],
                                ['Total', formatRupiah(transaction.total)],
                                ['Paid', formatRupiah(transaction.paid_amount)],
                                [
                                    'Remaining',
                                    formatRupiah(transaction.remaining_amount),
                                ],
                            ].map(([label, value]) => (
                                <div
                                    key={label}
                                    className="grid grid-cols-[100px_10px_1fr]"
                                >
                                    <span>{label}</span>
                                    <span>:</span>
                                    <span>{value}</span>
                                </div>
                            ))}
                        </div>

                        {transaction.delivery_type === 'delivery' && (
                            <div>
                                <Label>Driver Name</Label>

                                <Input
                                    value={data.driver_name}
                                    onChange={(e) =>
                                        setData('driver_name', e.target.value)
                                    }
                                />
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="payment_method">
                                Payment Method
                            </Label>

                            <Select
                                value={data.payment_method}
                                onValueChange={(value) =>
                                    setData('payment_method', value)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih metode pembayaran" />
                                </SelectTrigger>

                                <SelectContent align="start">
                                    <SelectItem value="cash">Cash</SelectItem>
                                    <SelectItem value="transfer">
                                        Transfer
                                    </SelectItem>
                                    <SelectItem value="qris">QRIS</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Amount</Label>

                            <Input
                                type="text"
                                inputMode="numeric"
                                value={formatRupiah(String(data.amount))}
                                onFocus={(e) => e.target.select()}
                                onChange={(e) => {
                                    const raw = e.target.value.replace(
                                        /\D/g,
                                        '',
                                    );
                                    let amount = raw ? Number(raw) : 0;

                                    if (amount > transaction.remaining_amount) {
                                        amount = transaction.remaining_amount;
                                    }

                                    setData('amount', amount);
                                }}
                            />
                        </div>

                        <Button onClick={submit} disabled={processing}>
                            Save
                        </Button>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
