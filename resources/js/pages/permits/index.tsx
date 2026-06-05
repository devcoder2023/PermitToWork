import { Link, router, usePage } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type PermitType = {
    id: number;
    name_en: string;
    name_ar: string;
    duration_type: string;
};

type Site = {
    id: number;
    name: string;
    project: {
        id: number;
        name: string;
    };
};

type WorkPermit = {
    id: number;
    permit_number: string;
    status: string;
    permit_type: PermitType;
    project: { id: number; name: string };
    site: { id: number; name: string };
    engineer: { id: number; name: string };
    supervisor: { id: number; name: string };
    sub_contractor: { id: number; name: string } | null;
    start_date: string;
    end_date: string;
    created_at: string;
};

type Props = {
    permits: {
        data: WorkPermit[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        status: string;
        permit_type: string;
        site: string;
        date_from: string;
        date_to: string;
        engineer: string;
        company: string;
    };
    permitTypes: PermitType[];
    sites: Site[];
};

const statusLabels: Record<string, string> = {
    new: 'New',
    under_review: 'Under Review',
    rejected: 'Rejected',
    approved: 'Approved',
    active: 'Active',
    daily_closed: 'Daily Closed',
    suspended: 'Suspended',
    terminated: 'Terminated',
    expired: 'Expired',
    archived: 'Archived',
};

const statusColors: Record<string, string> = {
    new: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    under_review:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    approved:
        'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400',
    daily_closed:
        'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
    suspended:
        'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
    terminated: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    expired:
        'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
    archived:
        'bg-gray-100 text-gray-600 dark:bg-gray-900/20 dark:text-gray-500',
};

function formatDate(date: string | null): string {
    if (!date) {
return '-';
}

    return new Date(date).toLocaleDateString();
}

export default function PermitIndex({
    permits,
    filters,
    permitTypes,
    sites,
}: Props) {
    const { props } = usePage();
    const user = props.auth?.user;

    const canCreatePermit = user?.role === 'execution_engineer';

    const handleFilter: FormEventHandler = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        router.get(
            '/permits',
            {
                status: formData.get('status'),
                permit_type: formData.get('permit_type'),
                site: formData.get('site'),
                date_from: formData.get('date_from'),
                date_to: formData.get('date_to'),
            },
            { preserveState: true },
        );
    };

    return (
        <AppLayout title="Permits">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                        Work Permits
                    </h2>
                    {canCreatePermit && (
                        <Link
                            href="/permits/create"
                            className="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black dark:bg-[#f53003] dark:hover:bg-[#d62a02]"
                        >
                            New Permit
                        </Link>
                    )}
                </div>

                {props.flash?.success && (
                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {props.flash.success}
                    </div>
                )}

                <form
                    onSubmit={handleFilter}
                    className="grid grid-cols-2 gap-4 md:grid-cols-5"
                >
                    <select
                        name="status"
                        defaultValue={filters.status}
                        className="rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                    >
                        <option value="">All Statuses</option>
                        <option value="new">New</option>
                        <option value="under_review">Under Review</option>
                        <option value="rejected">Rejected</option>
                        <option value="approved">Approved</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="terminated">Terminated</option>
                        <option value="expired">Expired</option>
                        <option value="archived">Archived</option>
                    </select>

                    <select
                        name="permit_type"
                        defaultValue={filters.permit_type}
                        className="rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                    >
                        <option value="">All Types</option>
                        {permitTypes.map((type) => (
                            <option key={type.id} value={type.id}>
                                {type.name_en}
                            </option>
                        ))}
                    </select>

                    <select
                        name="site"
                        defaultValue={filters.site}
                        className="rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                    >
                        <option value="">All Sites</option>
                        {sites.map((site) => (
                            <option key={site.id} value={site.id}>
                                {site.name}
                            </option>
                        ))}
                    </select>

                    <input
                        type="date"
                        name="date_from"
                        defaultValue={filters.date_from}
                        placeholder="From"
                        className="rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                    />

                    <input
                        type="date"
                        name="date_to"
                        defaultValue={filters.date_to}
                        placeholder="To"
                        className="rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                    />

                    <button
                        type="submit"
                        className="col-span-2 rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] md:col-span-1 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                    >
                        Filter
                    </button>
                </form>

                <div className="overflow-hidden rounded-lg border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <table className="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                        <thead className="bg-[#f5f5f4] dark:bg-[#1a1a19]">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Permit #
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Type
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Site
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Engineer
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    Dates
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                            {permits.data.map((permit) => (
                                <tr
                                    key={permit.id}
                                    className="hover:bg-[#f5f5f4] dark:hover:bg-[#1a1a19]"
                                >
                                    <td className="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                        <Link
                                            href={`/permits/${permit.id}`}
                                            className="text-[#f53003] hover:underline dark:text-[#FF4433]"
                                        >
                                            {permit.permit_number}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-sm whitespace-nowrap text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {permit.permit_type.name_en}
                                    </td>
                                    <td className="px-4 py-3 text-sm whitespace-nowrap">
                                        <span
                                            className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${statusColors[permit.status] ?? ''}`}
                                        >
                                            {statusLabels[permit.status] ??
                                                permit.status}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {permit.site.name}
                                    </td>
                                    <td className="px-4 py-3 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {permit.engineer.name}
                                    </td>
                                    <td className="px-4 py-3 text-sm whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]">
                                        {formatDate(permit.start_date)} -{' '}
                                        {formatDate(permit.end_date)}
                                    </td>
                                </tr>
                            ))}
                            {permits.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        No permits found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {permits.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Page {permits.current_page} of {permits.last_page}
                        </span>
                        <div className="flex gap-2">
                            {permits.prev_page_url && (
                                <Link
                                    href={permits.prev_page_url}
                                    className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                                >
                                    Previous
                                </Link>
                            )}
                            {permits.next_page_url && (
                                <Link
                                    href={permits.next_page_url}
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
