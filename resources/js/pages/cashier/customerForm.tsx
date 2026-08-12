import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Customer {
    id: number | null;
    name: string;
    phone: string;
    address: string;
    current_point: number;
}

interface CustomerOption {
    id: number;
    name: string;
    phone: string;
    address: string;
    current_point: number;
}

interface Props {
    customer: Customer;
    customers: CustomerOption[];
    showCustomerResult: boolean;

    pointsUsed: number;
    onPointsChange: (value: number) => void;

    onCustomerSearch: (value: string) => void;
    onCustomerSelect: (customer: CustomerOption) => void;
    onCustomerChange: (field: keyof Customer, value: string) => void;
}
export default function CustomerForm({
    customer,
    customers,
    showCustomerResult,

    pointsUsed,
    onPointsChange,

    onCustomerSearch,
    onCustomerChange,
    onCustomerSelect,
}: Props) {
    return (
        <div className="space-y-1 rounded-md border p-2">
            <div>
                <Label>Customer</Label>

                <Input
                    value={customer.name}
                    onChange={(e) => {
                        onCustomerSearch(e.target.value);

                        onCustomerChange('name', e.target.value);
                    }}
                    placeholder="Cari nama/no hp..."
                />

                {showCustomerResult &&
                    customer.name &&
                    customers.length > 0 && (
                        <div className="mt-1 max-h-48 overflow-y-auto rounded-md border bg-white">
                            {customers.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    className="block w-full border-b p-2 text-left hover:bg-gray-50"
                                    onClick={() => onCustomerSelect(item)}
                                >
                                    <div className="font-medium">
                                        {item.name}
                                    </div>

                                    <div className="text-xs text-gray-500">
                                        {item.phone}
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}
            </div>

            <div>
                <Label>No. HP</Label>

                <Input
                    value={customer.phone}
                    onChange={(e) => onCustomerChange('phone', e.target.value)}
                    placeholder="Masukkan nomor HP"
                />
            </div>

            <div>
                <Label>Alamat</Label>

                <Input
                    value={customer.address}
                    onChange={(e) =>
                        onCustomerChange('address', e.target.value)
                    }
                    placeholder="Masukkan alamat lengkap"
                />
            </div>
            <div>
                <Label>
                    Gunakan Point
                    <span className="ml-2 text-xs text-green-600">
                        Tersedia: {customer.current_point ?? 0}
                    </span>
                </Label>

                <Input
                    type="text"
                    value={pointsUsed ? pointsUsed.toLocaleString('id-ID') : ''}
                    onChange={(e) => {
                        const raw = e.target.value.replace(/[^0-9]/g, '');
                        let value = raw === '' ? 0 : Number(raw);

                        if (value > customer.current_point) {value = customer.current_point;}

                        onPointsChange(value);
                    }}
                    placeholder="Masukkan point yang digunakan"
                />
            </div>
        </div>
    );
}
