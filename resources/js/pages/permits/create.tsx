import { Link, useForm } from '@inertiajs/react';
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
};

type Supervisor = {
    id: number;
    name: string;
};

type Props = {
    permitTypes: PermitType[];
    sites: Site[];
    supervisors: Supervisor[];
};

export default function CreatePermit({
    permitTypes,
    sites,
    supervisors,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        permit_type_id: '',
        site_id: '',
        supervisor_id: '',
        work_description: '',
        start_date: '',
        end_date: '',
        shift: 'Morning',
        location_area: '',
        location_floor: '',
        location_description: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/permits');
    };

    const selectedPermitType = permitTypes.find(
        (t) => String(t.id) === data.permit_type_id,
    );

    const isDailyPermit = selectedPermitType?.duration_type === 'daily';

    const handlePermitTypeChange = (value: string) => {
        setData('permit_type_id', value);
        const type = permitTypes.find((t) => String(t.id) === value);

        if (type?.duration_type === 'daily' && data.start_date) {
            setData('end_date', data.start_date);
        }
    };

    const handleStartDateChange = (value: string) => {
        setData('start_date', value);

        if (isDailyPermit) {
            setData('end_date', value);
        }
    };

    return (
        <AppLayout title="Create Work Permit">
            <div className="max-w-3xl">
                <div className="mb-6">
                    <Link
                        href="/permits"
                        className="text-sm text-[#f53003] hover:underline dark:text-[#FF4433]"
                    >
                        &larr; Back to Permits
                    </Link>
                </div>

                <h2 className="mb-6 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    Create Work Permit
                </h2>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                htmlFor="permit_type_id"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Permit Type{' '}
                                <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="permit_type_id"
                                value={data.permit_type_id}
                                onChange={(e) =>
                                    handlePermitTypeChange(e.target.value)
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                            >
                                <option value="">Select type...</option>
                                {permitTypes.map((type) => (
                                    <option key={type.id} value={type.id}>
                                        {type.name_en} ({type.duration_type})
                                    </option>
                                ))}
                            </select>
                            {errors.permit_type_id && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.permit_type_id}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="supervisor_id"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Work Supervisor{' '}
                                <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="supervisor_id"
                                value={data.supervisor_id}
                                onChange={(e) =>
                                    setData('supervisor_id', e.target.value)
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                            >
                                <option value="">Select supervisor...</option>
                                {supervisors.map((supervisor) => (
                                    <option
                                        key={supervisor.id}
                                        value={supervisor.id}
                                    >
                                        {supervisor.name}
                                    </option>
                                ))}
                            </select>
                            {supervisors.length === 0 && (
                                <p className="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                                    No available supervisors. All supervisors
                                    have active permits.
                                </p>
                            )}
                            {errors.supervisor_id && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.supervisor_id}
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="work_description"
                            className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Work Description{' '}
                            <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            id="work_description"
                            value={data.work_description}
                            onChange={(e) =>
                                setData('work_description', e.target.value)
                            }
                            rows={4}
                            placeholder="Describe the work to be performed..."
                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A]"
                        />
                        {errors.work_description && (
                            <p className="mt-2 text-sm text-red-500">
                                {errors.work_description}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <label
                                htmlFor="start_date"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Start Date{' '}
                                <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="start_date"
                                type="date"
                                value={data.start_date}
                                onChange={(e) =>
                                    handleStartDateChange(e.target.value)
                                }
                                min={new Date().toISOString().split('T')[0]}
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                            />
                            {errors.start_date && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.start_date}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="end_date"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                End Date <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="end_date"
                                type="date"
                                value={data.end_date}
                                onChange={(e) =>
                                    setData('end_date', e.target.value)
                                }
                                min={
                                    data.start_date ||
                                    new Date().toISOString().split('T')[0]
                                }
                                disabled={isDailyPermit}
                                className={`w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] dark:border-[#3E3E3A] dark:text-[#EDEDEC] ${isDailyPermit ? 'cursor-not-allowed opacity-60 dark:bg-[#1a1a19]' : 'dark:bg-[#0a0a0a]'}`}
                            />
                            {errors.end_date && (
                                <p className="mt-2 text-sm text-red-500">
                                    {errors.end_date}
                                </p>
                            )}
                            {isDailyPermit && (
                                <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Auto-set to same day
                                </p>
                            )}
                            {selectedPermitType && !isDailyPermit && (
                                <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Max 7 days
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="shift"
                                className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Shift <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="shift"
                                value={data.shift}
                                onChange={(e) =>
                                    setData('shift', e.target.value)
                                }
                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                            >
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                                <option value="Night">Night</option>
                            </select>
                        </div>
                    </div>

                    <div className="border-t border-[#e3e3e0] pt-6 dark:border-[#3E3E3A]">
                        <h3 className="mb-4 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            Location Details
                        </h3>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    htmlFor="site_id"
                                    className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Site <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="site_id"
                                    value={data.site_id}
                                    onChange={(e) =>
                                        setData('site_id', e.target.value)
                                    }
                                    className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                >
                                    <option value="">Select site...</option>
                                    {sites.map((site) => (
                                        <option key={site.id} value={site.id}>
                                            {site.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.site_id && (
                                    <p className="mt-2 text-sm text-red-500">
                                        {errors.site_id}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="location_area"
                                    className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Area <span className="text-red-500">*</span>
                                </label>
                                <input
                                    id="location_area"
                                    type="text"
                                    value={data.location_area}
                                    onChange={(e) =>
                                        setData('location_area', e.target.value)
                                    }
                                    placeholder="e.g., Area87, Filling Zone"
                                    className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A]"
                                />
                                {errors.location_area && (
                                    <p className="mt-2 text-sm text-red-500">
                                        {errors.location_area}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="location_floor"
                                    className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Floor{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <input
                                    id="location_floor"
                                    type="text"
                                    value={data.location_floor}
                                    onChange={(e) =>
                                        setData(
                                            'location_floor',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g., Basement, Ground, 1st"
                                    className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A]"
                                />
                                {errors.location_floor && (
                                    <p className="mt-2 text-sm text-red-500">
                                        {errors.location_floor}
                                    </p>
                                )}
                            </div>

                            <div className="col-span-2">
                                <label
                                    htmlFor="location_description"
                                    className="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Additional Location Description
                                </label>
                                <input
                                    id="location_description"
                                    type="text"
                                    value={data.location_description}
                                    onChange={(e) =>
                                        setData(
                                            'location_description',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g., Next to emergency stairs"
                                    className="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-[#1b1b18] placeholder-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] dark:placeholder-[#A1A09A]"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-4">
                        <button
                            type="submit"
                            disabled={processing || supervisors.length === 0}
                            className="rounded-md bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-[#f53003] dark:hover:bg-[#d62a02] dark:disabled:bg-[#f53003]"
                        >
                            Create Permit
                        </button>
                        <Link
                            href="/permits"
                            className="rounded-md border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a19]"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
