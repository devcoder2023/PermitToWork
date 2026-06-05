import { Link, router, usePage } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type SubContractor = {
    id: number;
    name: string;
    contact_email: string;
    contact_phone: string | null;
    address: string | null;
    is_active: boolean;
};

type Props = {
    subContractors: {
        data: SubContractor[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        search: string;
    };
};

export default function SubContractorIndex({ subContractors, filters }: Props) {
    const { props } = usePage();

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        const search = formData.get('search') as string;
        router.get(
            '/admin/sub-contractors',
            { search },
            { preserveState: true },
        );
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this sub-contractor?')) {
            router.delete(`/admin/sub-contractors/${id}`);
        }
    };

    return (
        <AppLayout title="Sub-Contractors">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                        Sub-Contractors
                    </h2>
                    <Link
                        href="/admin/sub-contractors/create"
                        className="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black dark:bg-[#f53003] dark:hover:bg-[#d62a02]"
                    >
                        Add Sub-Contractor
                    </Link>
                </div>

                {props.flash?.success && (
                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {props.flash.success as string}
                    </div>
                )}

                <form onSubmit={handleSearch} className="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        defaultValue={filters.search}
                        placeholder="Search by name or email..."
                        className="flex-1 rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm text-[#1b1b18] placeholder-[#706f6c] focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />
                    <button
                        type="submit"
                        className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                    >
                        Search
                    </button>
                </form>

                <div className="overflow-hidden rounded-lg border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <table className="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                        <thead className="bg-[#f5f5f4] dark:bg-[#1a1a19]">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Name
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Contact Email
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Phone
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                            {subContractors.data.map((subContractor) => (
                                <tr key={subContractor.id}>
                                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {subContractor.name}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {subContractor.contact_email}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {subContractor.contact_phone || '-'}
                                    </td>
                                    <td className="px-6 py-4 text-sm whitespace-nowrap">
                                        <span
                                            className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${
                                                subContractor.is_active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                            }`}
                                        >
                                            {subContractor.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm whitespace-nowrap">
                                        <Link
                                            href={`/admin/sub-contractors/${subContractor.id}/edit`}
                                            className="mr-3 text-[#f53003] hover:underline dark:text-[#FF4433]"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleDelete(subContractor.id)
                                            }
                                            className="text-red-600 hover:underline dark:text-red-400"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {subContractors.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-6 py-4 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        No sub-contractors found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {subContractors.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Page {subContractors.current_page} of{' '}
                            {subContractors.last_page}
                        </span>
                        <div className="flex gap-2">
                            {subContractors.prev_page_url && (
                                <Link
                                    href={subContractors.prev_page_url}
                                    className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                                >
                                    Previous
                                </Link>
                            )}
                            {subContractors.next_page_url && (
                                <Link
                                    href={subContractors.next_page_url}
                                    className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
