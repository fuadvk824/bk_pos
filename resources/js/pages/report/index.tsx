import { useRef } from 'react';
import { useReactToPrint } from 'react-to-print';

interface PayrollItem {
    name: string;
    keterangan?: string;
    amount: number;
}

interface Employee {
    name: string;
    employee_code: string;
    department?: {
        name: string;
    };
    position?: {
        name: string;
    };
}

interface Payroll {
    month: number;
    year: number;

    basic_salary: string;
    total_additions: string;
    total_deductions: string;
    net_salary: string;

    employee: Employee;
}

interface Props {
    payroll: Payroll;
    employee: Employee;
    additions: PayrollItem[];
    deductions: PayrollItem[];
    periodStart: string;
    periodEnd: string;
    totalWorkDays: number;
}

export default function SlipGaji({
    payroll,
    employee,
    additions,
    deductions,
    periodStart,
    periodEnd,
    totalWorkDays,
}: Props) {
    const printRef = useRef<HTMLDivElement>(null);

    const handlePrint = useReactToPrint({
        contentRef: printRef,
        documentTitle: `Slip Gaji - ${employee.name}`,

        fonts: [
            {
                family: 'Roboto Condensed',
                source: '/fonts/RobotoCondensed-Regular.ttf',
            },
            {
                family: 'Roboto Condensed',
                source: '/fonts/RobotoCondensed-Bold.ttf',
                weight: 'bold',
            },
        ],
    });

    const formatRupiah = (number: number) => {
        return new Intl.NumberFormat('id-ID').format(number);
    };

    return (
        <div
            className="min-h-screen bg-gray-100 p-5"
            style={{
                fontFamily: '"Roboto Condensed", sans-serif',
            }}
        >
            <div className="mb-5 flex justify-end print:hidden">
                <button onClick={() => handlePrint()} className="rounded bg-black px-4 py-2 text-white">
                    Cetak PDF
                </button>
            </div>

            <div ref={printRef} className="mx-auto w-[210mm] bg-amber-50 p-5 text-black">
                <div className="flex items-center gap-3">
                    <div className="w-44">
                        <img src="/logo/logo_gaji.jpg" alt="" className="w-full object-contain" />
                    </div>

                    <div className="flex flex-1 justify-between border-b-2 border-black pb-2">
                        <div>
                            <h1 className="text-3xl font-bold">BISA KULAK JATIM</h1>

                            <p className="text-xs">Jl. Raya Kepatihan No. 49, Kec. Menganti</p>

                            <p className="text-xs">Kab. Gresik, Jawa Timur</p>
                        </div>

                        <div className="text-right">
                            <h1 className="text-3xl font-bold">SLIP GAJI</h1>

                            <p className="text-xs">
                                Periode{' '}
                                {new Date(payroll.year, payroll.month - 1).toLocaleString('id-ID', {
                                    month: 'long',
                                    year: 'numeric',
                                })}
                            </p>

                            <p className="text-xs italic">
                                {periodStart} - {periodEnd}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="mt-6 flex w-full justify-between">
                    <div className="flex w-1/2 space-y-1 text-xs font-bold">
                        <div className="flex w-44 flex-col gap-2">
                            <div className="flex w-full justify-between">
                                <span>Nama</span>
                                <span>:</span>
                            </div>
                            <div className="flex w-full justify-between">
                                <span>ID</span>
                                <span>:</span>
                            </div>
                        </div>

                        <div className="flex w-44 flex-col gap-2 ps-3">
                            <span>{employee.name}</span>
                            <span>{employee.employee_code}</span>
                        </div>
                    </div>

                    <div>
                        <div className="border-2 border-black px-5 py-2 text-center text-xs">
                            <div className="underline">Jumlah Hari Kerja</div>

                            <div className="mt-1 text-lg font-bold">{totalWorkDays}</div>
                        </div>
                    </div>
                </div>

                <div className="mt-6 w-full">
                    <div className="flex border-y-2 border-black py-2 text-xs font-bold">
                        <div className="w-1/2">PENDAPATAN</div>

                        <div className="w-1/2 pl-2.5">POTONGAN</div>
                    </div>

                    <div className="flex gap-5 pt-3">
                        <div className="w-1/2">
                            {additions.length > 0 ? (
                                additions.map((item, index) => (
                                    <div key={index} className="flex text-xs">
                                        <div className="flex w-44 justify-between">
                                            <span>{item.name}</span>
                                            <span>:</span>
                                        </div>

                                        <div className="flex flex-1 justify-between ps-3">
                                            <span>Rp</span>
                                            <span>{formatRupiah(item.amount)}</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-xs">-</div>
                            )}
                        </div>

                        <div className="w-1/2">
                            {deductions.length > 0 ? (
                                deductions.map((item, index) => (
                                    <div key={index} className="mb-1 flex justify-between text-xs">
                                        <div className="flex w-44 justify-between">
                                            <span>{item.name}</span>
                                            <span>:</span>
                                        </div>

                                        <div className="flex flex-1 justify-between ps-3">
                                            <span>Rp</span>
                                            <span>{formatRupiah(item.amount)}</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-xs">-</div>
                            )}
                        </div>
                    </div>

                    <div className="mt-3 flex border-y-2 border-black py-2 text-xs font-bold">
                        <div className="flex w-1/2 justify-between pr-2.5">
                            <div className="flex w-44 justify-between">
                                <span>JUMLAH PENDAPATAN</span>
                                <span>:</span>
                            </div>

                            <div className="flex flex-1 justify-between ps-3">
                                <span>Rp</span>
                                <span>{formatRupiah(Number(payroll.basic_salary) + Number(payroll.total_additions))}</span>
                            </div>
                        </div>
                        <div className="flex w-1/2 justify-between pl-2.5">
                            <div className="flex w-44 justify-between">
                                <span>JUMLAH POTONGAN</span>
                                <span>:</span>
                            </div>

                            <div className="flex flex-1 justify-between ps-3">
                                <span>Rp</span>
                                <span>{formatRupiah(Number(payroll.total_deductions))}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mt-8 flex justify-between">
                    <div className="flex w-1/2 pr-2.5 text-xs">
                        <div className="flex w-44 flex-col">
                            <div className="flex justify-between">
                                <span>GAJI YANG DITERIMA</span>
                                <span>:</span>
                            </div>
                            <div className="italic">(Take Home Pay)</div>
                        </div>
                        <div className="flex flex-1 justify-between ps-3">
                            <span>Rp</span>
                            <span>{formatRupiah(Number(payroll.net_salary))}</span>
                        </div>
                    </div>

                    <div className="relative pr-10 text-xs">
                        <div className="absolute inset-0 flex flex-col items-center justify-center">
                            <span>Dibuat Oleh :</span>

                            <span className="mt-10 font-bold">Admin Payroll</span>
                        </div>

                        <img src="/logo/logo_gaji.jpg" alt="" className="w-28 opacity-90" />
                    </div>
                </div>
            </div>
        </div>
    );
}