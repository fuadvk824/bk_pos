import { Head } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format-rupiah';
import { TransactionDetail } from '@/types/custom/transaction-detail';

interface Props {
    transaction: TransactionDetail;
}

export default function Show({ transaction }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Transaction',
                    href: '/transaction',
                },
                {
                    title: transaction.invoice_number,
                    href: '#',
                },
            ]}
        >
            <Head title={transaction.invoice_number} />

            <div className="space-y-6 p-5 mb-20">
                <div className="rounded-xl border p-4">
                    <h2 className="mb-4 text-lg font-semibold">
                        Informasi Transaksi
                    </h2>

                    <div className="grid grid-cols-2 gap-3 text-sm">
                        <div>Invoice :{transaction.invoice_number}</div>
                        <div>Tanggal :{transaction.created_at}</div>
                        <div>Status :{transaction.payment_status}</div>
                        <div>Delivery :{transaction.delivery_type}</div>
                        <div>Kasir :{transaction.cashier.name}</div>
                        <div>Store :{transaction.store.name}</div>
                        {transaction.driver_name && (
                            <div>Driver :{transaction.driver_name}</div>
                        )}
                    </div>
                </div>

                <div className="rounded-xl border p-4">
                    <h2 className="mb-4 text-lg font-semibold">Customer</h2>

                    <div className="space-y-2 text-sm">
                        <div>Nama :{transaction.customer?.name ?? '-'}</div>
                        <div>Telepon :{transaction.customer?.phone ?? '-'}</div>
                        <div>
                            Alamat :{transaction.customer?.address ?? '-'}
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border p-4">
                    <h2 className="mb-4 text-lg font-semibold">Produk</h2>

                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="p-2 text-left">Produk</th>
                                <th className="p-2 text-left">Qty</th>
                                <th className="p-2 text-left">Harga</th>
                                <th className="p-2 text-left">Diskon</th>
                                <th className="p-2 text-left">Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            {transaction.items.map((item) => (
                                <tr key={item.id} className="border-b">
                                    <td className="p-2">{item.product_name}</td>
                                    <td className="p-2">{item.quantity}</td>
                                    <td className="p-2">
                                        {formatRupiah(item.price)}
                                    </td>
                                    <td className="p-2 text-red-500">
                                        {formatRupiah(item.discount)}
                                    </td>
                                    <td className="p-2">
                                        {formatRupiah(item.subtotal)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="rounded-xl border p-4">
                    <h2 className="mb-4 text-lg font-semibold">Pembayaran</h2>

                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="p-2 text-left">Kasir</th>
                                <th className="p-2 text-left">Metode</th>
                                <th className="p-2 text-left">Nominal</th>
                                <th className="p-2 text-left">Tanggal</th>
                            </tr>
                        </thead>

                        <tbody>
                            {transaction.payments.map((payment) => (
                                <tr key={payment.id} className="border-b">
                                    <td className="p-2">
                                        {payment.cashier_name ?? '-'}
                                    </td>

                                    <td className="p-2">
                                        {payment.payment_method}
                                    </td>

                                    <td className="p-2">
                                        {formatRupiah(payment.amount)}
                                    </td>

                                    <td className="p-2">{payment.paid_at}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="rounded-xl border p-4">
                    <h2 className="mb-4 text-lg font-semibold">Ringkasan</h2>

                    <div className="space-y-2 text-right">
                        <div>
                            Subtotal :{formatRupiah(transaction.subtotal)}
                        </div>

                        <div>
                            Ongkir :{formatRupiah(transaction.shipping_cost)}
                        </div>

                        <div className="text-xl font-bold">
                            Total :{formatRupiah(transaction.total)}
                        </div>
                    </div>
                </div>

                {transaction.notes && (
                    <div className="rounded-xl border p-4">
                        <h2 className="mb-2 text-lg font-semibold">Catatan</h2>

                        <p className="text-sm whitespace-pre-line">
                            {transaction.notes}
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
