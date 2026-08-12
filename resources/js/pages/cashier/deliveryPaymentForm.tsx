import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { formatRupiah } from '@/lib/format-rupiah';

interface DeliveryPayment {
    deliveryType: string;
    shippingCost: number;

    notes: string;

    paymentStatus: string;
    paymentMethod: string;
    paymentAmount: number;
}

interface Props {
    value: DeliveryPayment;
    total: number;
    onChange: (value: DeliveryPayment) => void;
}

export default function DeliveryPaymentForm({ value, total, onChange }: Props) {
    return (
        <div className="space-y-1 rounded-md border p-2">
            <div>
                <Label>Tipe Pengiriman</Label>

                <RadioGroup
                    value={value.deliveryType}
                    onValueChange={(val) =>
                        onChange({
                            ...value,
                            deliveryType: val,
                            shippingCost:
                                val === 'pickup' ? 0 : value.shippingCost,
                        })
                    }
                    className="mt-1 flex"
                >
                    <div className="flex items-center space-x-2">
                        <RadioGroupItem value="pickup" id="pickup" />
                        <Label htmlFor="pickup">Pickup</Label>
                    </div>

                    <div className="flex items-center space-x-2">
                        <RadioGroupItem value="delivery" id="delivery" />
                        <Label htmlFor="delivery">Delivery</Label>
                    </div>
                </RadioGroup>
            </div>

            {value.deliveryType === 'delivery' && (
                <div>
                    <Label>Ongkir</Label>
                    <Input
                        type="text"
                        value={formatRupiah(value.shippingCost || 0)}
                        onChange={(e) => {
                            const raw = e.target.value.replace(/[^0-9]/g, '');

                            onChange({
                                ...value,
                                shippingCost: Number(raw),
                            });
                        }}
                    />
                </div>
            )}

            <div>
                <Label>Catatan (opsional)</Label>

                <Textarea
                    value={value.notes}
                    onChange={(e) =>
                        onChange({
                            ...value,
                            notes: e.target.value,
                        })
                    }
                />
            </div>

            <div>
                <Label>Status Pembayaran</Label>

                <Select
                    value={value.paymentStatus}
                    onValueChange={(val) =>
                        onChange({
                            ...value,
                            paymentStatus: val,
                        })
                    }
                >
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="unpaid">Belum Bayar</SelectItem>

                        <SelectItem value="partial">DP / Sebagian</SelectItem>

                        <SelectItem value="paid">Lunas</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {value.paymentStatus !== 'unpaid' && (
                <div>
                    <Label>Metode Pembayaran</Label>

                    <Select
                        value={value.paymentMethod}
                        onValueChange={(val) =>
                            onChange({
                                ...value,
                                paymentMethod: val,
                            })
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value="cash">Cash</SelectItem>
                            <SelectItem value="transfer">Transfer</SelectItem>
                            <SelectItem value="qris">QRIS</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            )}

            {value.paymentStatus === 'partial' && (
                <div>
                    <Label>Nominal DP</Label>

                    <Input
                        type="text"
                        value={formatRupiah(value.paymentAmount || 0)}
                        onChange={(e) => {
                            const raw = e.target.value.replace(/[^0-9]/g, '');

                            onChange({
                                ...value,
                                paymentAmount: Number(raw),
                            });
                        }}
                    />
                </div>
            )}

            {value.paymentStatus === 'paid' && (
                <div className="rounded-md border bg-green-50 p-3 text-xs">
                    <div>Pembayaran penuh sebesar </div>
                    <strong>{formatRupiah(total)}</strong>
                </div>
            )}
        </div>
    );
}
