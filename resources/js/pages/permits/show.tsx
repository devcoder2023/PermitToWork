import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEventHandler } from 'react';
import AppLayout from '@/components/layouts/app-layout';

type User = {
    id: number;
    name: string;
};

type PermitType = {
    id: number;
    name_en: string;
    name_ar: string;
    duration_type: string;
};

type ApprovalTimelineItem = {
    stage: string;
    label: string;
    user: User | null;
    decision: string | null;
    reason: string | null;
    timestamp: string | null;
};

type FollowUp = {
    id: number;
    user: User;
    date: string;
    notes: string | null;
    is_first: boolean;
    created_at: string;
};

type Closure = {
    id: number;
    user: User;
    date: string;
    closed_at: string;
};

type DailyOperations = {
    today: string;
    is_daily_closed: boolean;
    is_last_day: boolean;
    is_within_period: boolean;
    supervisor_follow_up_today: boolean;
    hse_follow_up_today: boolean;
    supervisor_closed_today: boolean;
    hse_closed_today: boolean;
    user_closed_today: boolean;
    user_follow_up_today: boolean;
    can_first_follow_up: boolean;
    can_record_follow_up: boolean;
    can_supervisor_record_follow_up: boolean;
    can_hse_record_follow_up: boolean;
    can_close_day: boolean;
    can_supervisor_close_day: boolean;
    can_hse_close_day: boolean;
    work_supervisor_accepted: boolean;
    first_follow_up_done: boolean;
    is_hse_assigned: boolean;
    is_supervisor_assigned: boolean;
    follow_ups: FollowUp[];
    closures: Closure[];
};

type WorkPermit = {
    id: number;
    permit_number: string;
    status: string;
    permit_type: PermitType;
    project: { id: number; name: string };
    site: { id: number; name: string };
    engineer: User;
    supervisor: User;
    sub_contractor: { id: number; name: string } | null;
    location_area: string;
    location_floor: string;
    location_description: string | null;
    work_description: string;
    request_date: string;
    start_date: string;
    end_date: string;
    shift: string;
    rejection_reason: string | null;
    created_at: string;
};

type Props = {
    permit: WorkPermit;
    approvalTimeline: ApprovalTimelineItem[];
    dailyOperations: DailyOperations;
    observations: {
        can_create: boolean;
        can_resolve: boolean;
        can_review: boolean;
        can_suspend: boolean;
        can_terminate: boolean;
        can_request_resume: boolean;
        can_approve_resume: boolean;
        is_suspended: boolean;
        is_terminated: boolean;
        resumption_requested: boolean;
        suspension_reason: string | null;
        termination_reason: string | null;
        list: {
            id: number;
            description: string;
            status: string;
            resolution_note: string | null;
            rejection_reason: string | null;
            created_by: User;
            created_at: string;
            resolved_at: string | null;
            closed_at: string | null;
            attachments: {
                id: number;
                file_name: string;
                file_path: string;
                mime_type: string;
            }[];
            can_resolve: boolean;
            can_review: boolean;
        }[];
    };
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

function formatDateTime(datetime: string | null): string {
    if (!datetime) {
        return '-';
    }

    return new Date(datetime).toLocaleString();
}

function formatTime(datetime: string | null): string {
    if (!datetime) {
        return '-';
    }

    return new Date(datetime).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function ShowPermit({
    permit,
    approvalTimeline,
    dailyOperations,
    observations,
}: Props) {
    const { props } = usePage();
    const authUser = props.auth?.user;

    const rejectForm = useForm({ reason: '' });
    const followUpForm = useForm({ notes: '' });
    const firstFollowUpForm = useForm({ notes: '' });
    const observationForm = useForm({
        description: '',
        attachments: [] as File[],
    });
    const resolveObservationForm = useForm({ resolution_note: '' });
    const suspensionForm = useForm({ suspension_reason: '' });
    const resumptionForm = useForm({ resumption_note: '' });
    const resumptionApprovalForm = useForm({ resumption_approval_note: '' });
    const terminationForm = useForm({ termination_reason: '' });
    const [resumptionDecisionError, setResumptionDecisionError] = useState('');
    const [resolutionReviewByObsId, setResolutionReviewByObsId] = useState<
        Record<number, string>
    >({});
    const [resolutionReviewErrorByObsId, setResolutionReviewErrorByObsId] =
        useState<Record<number, string>>({});

    const canEdit =
        authUser?.role === 'execution_engineer' &&
        (permit.status === 'new' || permit.status === 'rejected');
    const canCancel =
        authUser?.role === 'execution_engineer' && permit.status === 'new';

    const canApproveSiteManager =
        authUser?.role === 'site_manager' && permit.status === 'new';
    const canRejectSiteManager =
        authUser?.role === 'site_manager' && permit.status === 'new';
    const canApprovePermitOfficer =
        authUser?.role === 'permit_officer' && permit.status === 'under_review';
    const canRejectPermitOfficer =
        authUser?.role === 'permit_officer' && permit.status === 'under_review';
    const canAcceptWorkSupervisor =
        authUser?.role === 'work_supervisor' &&
        permit.status === 'approved' &&
        authUser?.id === permit.supervisor.id &&
        !dailyOperations.work_supervisor_accepted;
    const canDeclineWorkSupervisor =
        authUser?.role === 'work_supervisor' &&
        permit.status === 'approved' &&
        authUser?.id === permit.supervisor.id &&
        !dailyOperations.work_supervisor_accepted;

    const showApprovalActions =
        canApproveSiteManager ||
        canRejectSiteManager ||
        canApprovePermitOfficer ||
        canRejectPermitOfficer ||
        canAcceptWorkSupervisor ||
        canDeclineWorkSupervisor;

    const showDailyOperations =
        dailyOperations.work_supervisor_accepted &&
        (dailyOperations.is_hse_assigned ||
            dailyOperations.is_supervisor_assigned);

    const handleApprove = (url: string) => {
        if (confirm('Are you sure you want to approve?')) {
            router.post(url);
        }
    };

    const handleReject =
        (url: string): FormEventHandler =>
        (e) => {
            e.preventDefault();

            if (!rejectForm.data.reason.trim()) {
                alert('Please provide a reason for rejection.');

                return;
            }

            rejectForm.post(url);
        };

    const handleCancel = () => {
        if (confirm('Are you sure you want to cancel this permit?')) {
            router.delete(`/permits/${permit.id}`);
        }
    };

    const handleFirstFollowUp: FormEventHandler = (e) => {
        e.preventDefault();
        firstFollowUpForm.post(`/permits/${permit.id}/first-follow-up`);
    };

    const handleRecordFollowUp: FormEventHandler = (e) => {
        e.preventDefault();

        if (!followUpForm.data.notes.trim()) {
            alert('Please provide notes for the follow-up.');

            return;
        }

        followUpForm.post(`/permits/${permit.id}/follow-up`, {
            onSuccess: () => followUpForm.reset('notes'),
        });
    };

    const handleCloseDay = () => {
        if (confirm('Are you sure you want to close the day?')) {
            router.post(`/permits/${permit.id}/close-day`);
        }
    };

    return (
        <AppLayout title={`Permit ${permit.permit_number}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href="/permits"
                            className="text-sm text-[#f53003] hover:underline dark:text-[#FF4433]"
                        >
                            &larr; Back to Permits
                        </Link>
                    </div>
                    <div className="flex gap-2">
                        {canEdit && (
                            <Link
                                href={`/permits/${permit.id}/edit`}
                                className="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black dark:bg-[#f53003] dark:hover:bg-[#d62a02]"
                            >
                                Edit
                            </Link>
                        )}
                        {canCancel && (
                            <button
                                type="button"
                                onClick={handleCancel}
                                className="rounded-md border border-red-500 px-4 py-2 text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                            >
                                Cancel
                            </button>
                        )}
                    </div>
                </div>

                {props.flash?.success && (
                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {props.flash.success}
                    </div>
                )}
                {props.flash?.error && (
                    <div className="rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                        {props.flash.error}
                    </div>
                )}

                <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mb-6 flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                {permit.permit_number}
                            </h1>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                {permit.permit_type.name_en} Permit
                            </p>
                        </div>
                        <span
                            className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${statusColors[permit.status] ?? ''}`}
                        >
                            {statusLabels[permit.status] ?? permit.status}
                        </span>
                    </div>

                    {permit.rejection_reason && (
                        <div className="mb-6 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                            <h4 className="mb-1 text-sm font-semibold text-red-800 dark:text-red-400">
                                Rejection Reason
                            </h4>
                            <p className="text-sm text-red-700 dark:text-red-300">
                                {permit.rejection_reason}
                            </p>
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3">
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Project
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {permit.project.name}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Site
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {permit.site.name}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Company
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {permit.sub_contractor?.name ??
                                    'Main Contractor'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Engineer
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {permit.engineer.name}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Supervisor
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {permit.supervisor.name}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Shift
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {permit.shift}
                            </dd>
                        </div>
                    </div>

                    <div className="mt-6 border-t border-[#e3e3e0] pt-6 dark:border-[#3E3E3A]">
                        <h3 className="mb-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            Location
                        </h3>
                        <p className="text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                            {permit.site.name} → {permit.location_area} →{' '}
                            {permit.location_floor}
                            {permit.location_description &&
                                ` → ${permit.location_description}`}
                        </p>
                    </div>

                    <div className="mt-6 border-t border-[#e3e3e0] pt-6 dark:border-[#3E3E3A]">
                        <h3 className="mb-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            Work Description
                        </h3>
                        <p className="text-sm whitespace-pre-wrap text-[#1b1b18] dark:text-[#EDEDEC]">
                            {permit.work_description}
                        </p>
                    </div>

                    <div className="mt-6 grid grid-cols-3 gap-4 border-t border-[#e3e3e0] pt-6 dark:border-[#3E3E3A]">
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Request Date
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {formatDate(permit.request_date)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Start Date
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {formatDate(permit.start_date)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                End Date
                            </dt>
                            <dd className="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {formatDate(permit.end_date)}
                            </dd>
                        </div>
                    </div>
                </div>

                <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <h2 className="mb-4 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                        Approval Timeline
                    </h2>
                    <div className="space-y-4">
                        {approvalTimeline.map((item) => {
                            const isApproved = item.decision === 'approved';
                            const isRejected = item.decision === 'rejected';
                            const isPending = !item.decision;

                            return (
                                <div
                                    key={item.stage}
                                    className="flex items-start gap-4"
                                >
                                    <div
                                        className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${isApproved ? 'bg-green-100 dark:bg-green-900/30' : isRejected ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-800'}`}
                                    >
                                        {isApproved && (
                                            <svg
                                                className="h-5 w-5 text-green-600 dark:text-green-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                        )}
                                        {isRejected && (
                                            <svg
                                                className="h-5 w-5 text-red-600 dark:text-red-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        )}
                                        {isPending && (
                                            <svg
                                                className="h-5 w-5 text-gray-400 dark:text-gray-500"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <h4 className="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                                {item.label}
                                            </h4>
                                            {item.decision && (
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${isApproved ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'}`}
                                                >
                                                    {isApproved
                                                        ? 'Approved'
                                                        : 'Rejected'}
                                                </span>
                                            )}
                                            {isPending && (
                                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                    Pending
                                                </span>
                                            )}
                                        </div>
                                        {item.user && (
                                            <p className="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                                {item.user.name}
                                            </p>
                                        )}
                                        {item.timestamp && (
                                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                {formatDateTime(item.timestamp)}
                                            </p>
                                        )}
                                        {item.reason && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                                Reason: {item.reason}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {showApprovalActions && (
                    <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 className="mb-4 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            Approval Actions
                        </h2>
                        {canApproveSiteManager && (
                            <div className="mb-4">
                                <button
                                    type="button"
                                    onClick={() =>
                                        handleApprove(
                                            `/permits/${permit.id}/approve/site-manager`,
                                        )
                                    }
                                    className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                >
                                    Approve (Site Manager)
                                </button>
                            </div>
                        )}
                        {canRejectSiteManager && (
                            <form
                                onSubmit={handleReject(
                                    `/permits/${permit.id}/reject/site-manager`,
                                )}
                                className="space-y-3"
                            >
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        Rejection Reason{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        value={rejectForm.data.reason}
                                        onChange={(e) =>
                                            rejectForm.setData(
                                                'reason',
                                                e.target.value,
                                            )
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                        placeholder="Provide reason for rejection..."
                                    />
                                </div>
                                <button
                                    type="submit"
                                    className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                >
                                    Reject (Site Manager)
                                </button>
                            </form>
                        )}
                        {canApprovePermitOfficer && (
                            <div className="mb-4">
                                <button
                                    type="button"
                                    onClick={() =>
                                        handleApprove(
                                            `/permits/${permit.id}/approve/permit-officer`,
                                        )
                                    }
                                    className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                >
                                    Approve (Permit Officer)
                                </button>
                            </div>
                        )}
                        {canRejectPermitOfficer && (
                            <form
                                onSubmit={handleReject(
                                    `/permits/${permit.id}/reject/permit-officer`,
                                )}
                                className="space-y-3"
                            >
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        Rejection Reason{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        value={rejectForm.data.reason}
                                        onChange={(e) =>
                                            rejectForm.setData(
                                                'reason',
                                                e.target.value,
                                            )
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                        placeholder="Provide reason for rejection..."
                                    />
                                </div>
                                <button
                                    type="submit"
                                    className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                >
                                    Reject (Permit Officer)
                                </button>
                            </form>
                        )}
                        {canAcceptWorkSupervisor && (
                            <div className="mb-4">
                                <button
                                    type="button"
                                    onClick={() =>
                                        handleApprove(
                                            `/permits/${permit.id}/accept/work-supervisor`,
                                        )
                                    }
                                    className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                >
                                    Accept Work Assignment
                                </button>
                            </div>
                        )}
                        {canDeclineWorkSupervisor && (
                            <form
                                onSubmit={handleReject(
                                    `/permits/${permit.id}/decline/work-supervisor`,
                                )}
                                className="space-y-3"
                            >
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        Decline Reason{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        value={rejectForm.data.reason}
                                        onChange={(e) =>
                                            rejectForm.setData(
                                                'reason',
                                                e.target.value,
                                            )
                                        }
                                        rows={2}
                                        className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                        placeholder="Provide reason for declining..."
                                    />
                                </div>
                                <button
                                    type="submit"
                                    className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                >
                                    Decline Work Assignment
                                </button>
                            </form>
                        )}
                    </div>
                )}

                {showDailyOperations && (
                    <div className="rounded-lg border-2 border-emerald-500 bg-white p-6 dark:border-emerald-600 dark:bg-[#161615]">
                        <h2 className="mb-4 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            Daily Operations
                        </h2>

                        <div className="mb-6 rounded-md bg-emerald-50 p-4 dark:bg-emerald-900/20">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-semibold text-emerald-800 dark:text-emerald-400">
                                        Today:{' '}
                                        {formatDate(dailyOperations.today)}
                                    </p>

                                    {dailyOperations.is_daily_closed && (
                                        <p className="mt-1 text-xs font-semibold text-orange-600 dark:text-orange-400">
                                            ⚠ This is permit closed today
                                        </p>
                                    )}

                                    {dailyOperations.is_last_day && (
                                        <p className="mt-1 text-xs font-semibold text-orange-600 dark:text-orange-400">
                                            ⚠ This is the last day of the permit
                                        </p>
                                    )}
                                    {!dailyOperations.is_within_period && (
                                        <p className="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">
                                            ⚠ Today is outside the permit period
                                        </p>
                                    )}
                                </div>
                                <div className="text-right">
                                    <div className="mb-2">
                                        <span className="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            WS Follow-up:{' '}
                                        </span>
                                        {dailyOperations.supervisor_follow_up_today ? (
                                            <span className="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                ✓ Done
                                            </span>
                                        ) : (
                                            <span className="ml-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                ⏳ Pending
                                            </span>
                                        )}
                                    </div>
                                    <div className="mb-2">
                                        <span className="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            HSE Follow-up:{' '}
                                        </span>
                                        {dailyOperations.hse_follow_up_today ? (
                                            <span className="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                ✓ Done
                                            </span>
                                        ) : (
                                            <span className="ml-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                ⏳ Pending
                                            </span>
                                        )}
                                    </div>
                                    <div className="mb-2">
                                        <span className="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            WS Closure:{' '}
                                        </span>
                                        {dailyOperations.supervisor_closed_today ? (
                                            <span className="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                ✓ Closed
                                            </span>
                                        ) : (
                                            <span className="ml-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                ⏳ Pending
                                            </span>
                                        )}
                                    </div>
                                    <div>
                                        <span className="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            HSE Closure:{' '}
                                        </span>
                                        {dailyOperations.hse_closed_today ? (
                                            <span className="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                ✓ Closed
                                            </span>
                                        ) : (
                                            <span className="ml-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                ⏳ Pending
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {dailyOperations.can_first_follow_up && (
                            <div className="mb-6 rounded-lg border-2 border-green-500 bg-green-50 p-4 dark:border-green-600 dark:bg-green-900/10">
                                <div className="mb-3 flex items-center gap-2">
                                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white">
                                        1
                                    </span>
                                    <h3 className="text-base font-semibold text-green-800 dark:text-green-400">
                                        First Follow-Up (HSE Officer)
                                    </h3>
                                </div>
                                <p className="mb-3 text-sm text-green-700 dark:text-green-300">
                                    Complete the first follow-up to activate
                                    this permit.
                                </p>
                                <form
                                    onSubmit={handleFirstFollowUp}
                                    className="space-y-3"
                                >
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                            Notes (optional)
                                        </label>
                                        <textarea
                                            value={firstFollowUpForm.data.notes}
                                            onChange={(e) =>
                                                firstFollowUpForm.setData(
                                                    'notes',
                                                    e.target.value,
                                                )
                                            }
                                            rows={3}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                            placeholder="Add notes about site conditions..."
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        className="rounded-md bg-green-600 px-6 py-2 text-sm font-semibold text-white hover:bg-green-700"
                                    >
                                        Complete First Follow-Up & Activate
                                        Permit
                                    </button>
                                </form>
                            </div>
                        )}

                        {dailyOperations.can_supervisor_record_follow_up && (
                            <div className="mb-6 rounded-lg border-2 border-blue-500 bg-blue-50 p-4 dark:border-blue-600 dark:bg-blue-900/10">
                                <div className="mb-3 flex items-center gap-2">
                                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">
                                        2
                                    </span>
                                    <h3 className="text-base font-semibold text-blue-800 dark:text-blue-400">
                                        Daily Follow-Up (Work Supervisor)
                                    </h3>
                                </div>
                                <form
                                    onSubmit={handleRecordFollowUp}
                                    className="space-y-3"
                                >
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                            Follow-Up Notes{' '}
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        </label>
                                        <textarea
                                            value={followUpForm.data.notes}
                                            onChange={(e) =>
                                                followUpForm.setData(
                                                    'notes',
                                                    e.target.value,
                                                )
                                            }
                                            rows={3}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                            placeholder="Record observations..."
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        className="rounded-md bg-blue-600 px-6 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                    >
                                        Record Follow-Up
                                    </button>
                                </form>
                            </div>
                        )}

                        {dailyOperations.can_hse_record_follow_up && (
                            <div className="mb-6 rounded-lg border-2 border-purple-500 bg-purple-50 p-4 dark:border-purple-600 dark:bg-purple-900/10">
                                <div className="mb-3 flex items-center gap-2">
                                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-sm font-bold text-white">
                                        3
                                    </span>
                                    <h3 className="text-base font-semibold text-purple-800 dark:text-purple-400">
                                        Daily Follow-Up (HSE Officer)
                                    </h3>
                                </div>
                                <p className="mb-3 text-sm text-purple-700 dark:text-purple-300">
                                    Work Supervisor has completed follow-up. You
                                    can now record your follow-up.
                                </p>
                                <form
                                    onSubmit={handleRecordFollowUp}
                                    className="space-y-3"
                                >
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                            Follow-Up Notes{' '}
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        </label>
                                        <textarea
                                            value={followUpForm.data.notes}
                                            onChange={(e) =>
                                                followUpForm.setData(
                                                    'notes',
                                                    e.target.value,
                                                )
                                            }
                                            rows={3}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                            placeholder="Record safety observations..."
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        className="rounded-md bg-purple-600 px-6 py-2 text-sm font-semibold text-white hover:bg-purple-700"
                                    >
                                        Record Follow-Up
                                    </button>
                                </form>
                            </div>
                        )}

                        {dailyOperations.can_supervisor_close_day && (
                            <div className="mb-6 rounded-lg border-2 border-gray-500 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-800/50">
                                <div className="mb-3 flex items-center gap-2">
                                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-600 text-sm font-bold text-white">
                                        4
                                    </span>
                                    <h3 className="text-base font-semibold text-gray-800 dark:text-gray-300">
                                        Close Day (Work Supervisor)
                                    </h3>
                                </div>
                                <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                    Both follow-ups completed. You can now close
                                    the day.
                                </p>
                                <button
                                    type="button"
                                    onClick={handleCloseDay}
                                    className="rounded-md bg-gray-600 px-6 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                                >
                                    Close Day
                                </button>
                            </div>
                        )}

                        {dailyOperations.can_hse_close_day && (
                            <div className="mb-6 rounded-lg border-2 border-orange-500 bg-orange-50 p-4 dark:border-orange-600 dark:bg-orange-900/10">
                                <div className="mb-3 flex items-center gap-2">
                                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-orange-600 text-sm font-bold text-white">
                                        5
                                    </span>
                                    <h3 className="text-base font-semibold text-orange-800 dark:text-orange-400">
                                        Close Day (HSE Officer)
                                    </h3>
                                </div>
                                <p className="mb-3 text-sm text-orange-700 dark:text-orange-300">
                                    Work Supervisor has closed the day. You can
                                    now close.
                                </p>
                                <button
                                    type="button"
                                    onClick={handleCloseDay}
                                    className="rounded-md bg-orange-600 px-6 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                                >
                                    Close Day
                                </button>
                            </div>
                        )}

                        {dailyOperations.follow_ups.length > 0 && (
                            <div className="mt-6 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                                <h3 className="mb-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Follow-Ups History
                                </h3>
                                <div className="space-y-2">
                                    {dailyOperations.follow_ups.map(
                                        (followUp) => (
                                            <div
                                                key={followUp.id}
                                                className="rounded-md border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                                            {followUp.user.name}
                                                        </span>
                                                        {followUp.is_first && (
                                                            <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                                First Follow-Up
                                                            </span>
                                                        )}
                                                    </div>
                                                    <span className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                        {formatDate(
                                                            followUp.date,
                                                        )}{' '}
                                                        at{' '}
                                                        {formatTime(
                                                            followUp.created_at,
                                                        )}
                                                    </span>
                                                </div>
                                                {followUp.notes && (
                                                    <p className="mt-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                                        {followUp.notes}
                                                    </p>
                                                )}
                                            </div>
                                        ),
                                    )}
                                </div>
                            </div>
                        )}

                        {dailyOperations.closures.length > 0 && (
                            <div className="mt-6 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                                <h3 className="mb-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Daily Closures History
                                </h3>
                                <div className="space-y-2">
                                    {dailyOperations.closures.map((closure) => (
                                        <div
                                            key={closure.id}
                                            className="rounded-md border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                                        {closure.user.name}
                                                    </span>
                                                    <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                        Closed
                                                    </span>
                                                </div>
                                                <span className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    {formatDate(closure.date)}{' '}
                                                    at{' '}
                                                    {formatTime(
                                                        closure.closed_at,
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            Observations & Safety
                        </h2>
                    </div>

                    {observations.is_suspended && (
                        <div className="mb-6 rounded-md bg-orange-50 p-4 dark:bg-orange-900/20">
                            <h4 className="mb-1 text-sm font-semibold text-orange-800 dark:text-orange-400">
                                Permit Suspended
                            </h4>
                            <p className="text-sm text-orange-700 dark:text-orange-300">
                                Reason: {observations.suspension_reason}
                            </p>

                            {observations.resumption_requested && (
                                <p className="mt-2 text-sm font-medium text-orange-600 dark:text-orange-400">
                                    Resumption requested - awaiting approval
                                </p>
                            )}
                            
                            {observations.can_request_resume && !observations.resumption_requested && (
                                <div className="mb-4 mt-6">
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();

                                            if (
                                                !resumptionForm.data.resumption_note.trim()
                                            ) {
                                                alert(
                                                    'Please provide a resumption explanation.',
                                                );

                                                return;
                                            }

                                            router.post(
                                                `/permits/${permit.id}/request-resume`,
                                                resumptionForm.data,
                                                {
                                                    onSuccess: () => {
                                                        resumptionForm.reset(
                                                            'resumption_note',
                                                        );
                                                    },
                                                },
                                            );
                                        }}
                                        className="space-y-2"
                                    >
                                        <input
                                            type="text"
                                            value={
                                                resumptionForm.data
                                                    .resumption_note
                                            }
                                            onChange={(e) =>
                                                resumptionForm.setData(
                                                    'resumption_note',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Explanation for resumption..."
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                        >
                                            Request Resumption
                                        </button>
                                    </form>
                                </div>
                            )}

                            {observations.can_approve_resume &&
                                observations.resumption_requested && (
                                    <div className="mt-4 mb-4 space-y-2">
                                        <label className="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                            Note for approval (optional) or
                                            rejection reason (required if
                                            rejecting)
                                        </label>
                                        <input
                                            type="text"
                                            value={
                                                resumptionApprovalForm.data
                                                    .resumption_approval_note
                                            }
                                            onChange={(e) => {
                                                setResumptionDecisionError('');
                                                resumptionApprovalForm.setData(
                                                    'resumption_approval_note',
                                                    e.target.value,
                                                );
                                            }}
                                            placeholder="Approval note or rejection reason..."
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                        />
                                        {resumptionDecisionError && (
                                            <p className="text-sm text-red-600 dark:text-red-400">
                                                {resumptionDecisionError}
                                            </p>
                                        )}
                                        <div className="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setResumptionDecisionError(
                                                        '',
                                                    );
                                                    router.post(
                                                        `/permits/${permit.id}/approve-resume`,
                                                        resumptionApprovalForm.data,
                                                    );
                                                }}
                                                className="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                                            >
                                                Approve Resumption
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    const reason =
                                                        resumptionApprovalForm.data.resumption_approval_note.trim();

                                                    if (!reason) {
                                                        setResumptionDecisionError(
                                                            'Please provide a rejection reason.',
                                                        );

                                                        return;
                                                    }

                                                    setResumptionDecisionError(
                                                        '',
                                                    );
                                                    router.post(
                                                        `/permits/${permit.id}/reject-resume`,
                                                        {
                                                            resumption_rejection_reason:
                                                                reason,
                                                        },
                                                    );
                                                }}
                                                className="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                                            >
                                                Reject Resumption
                                            </button>
                                        </div>
                                    </div>
                                )}
                            
                        </div>
                    )}

                    {observations.is_terminated && (
                        <div className="mb-6 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                            <h4 className="mb-1 text-sm font-semibold text-red-800 dark:text-red-400">
                                Permit Terminated
                            </h4>
                            <p className="text-sm text-red-700 dark:text-red-300">
                                Reason: {observations.termination_reason}
                            </p>
                        </div>
                    )}

                    {observations.can_create && permit.status === 'active' && (
                        <div className="mb-6 rounded-lg border-2 border-red-500 bg-red-50 p-4 dark:border-red-600 dark:bg-red-900/10">
                            <h3 className="mb-3 text-base font-semibold text-red-800 dark:text-red-400">
                                Report Observation
                            </h3>
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const formData = new FormData();
                                    formData.append(
                                        'description',
                                        observationForm.data.description,
                                    );
                                    observationForm.data.attachments.forEach(
                                        (file) => {
                                            formData.append(
                                                'attachments[]',
                                                file,
                                            );
                                        },
                                    );
                                    router.post(
                                        `/permits/${permit.id}/observations`,
                                        formData,
                                        {
                                            onSuccess: () => {
                                                observationForm.reset();
                                            },
                                        },
                                    );
                                }}
                                className="space-y-3"
                            >
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        Description{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        value={observationForm.data.description}
                                        onChange={(e) =>
                                            observationForm.setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        rows={4}
                                        className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                        placeholder="Describe the safety observation..."
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        Attachments (optional)
                                    </label>
                                    <input
                                        type="file"
                                        multiple
                                        accept="image/*,.pdf"
                                        onChange={(e) => {
                                            const files = e.target.files
                                                ? Array.from(e.target.files)
                                                : [];
                                            observationForm.setData(
                                                'attachments',
                                                files,
                                            );
                                        }}
                                        className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    className="rounded-md bg-red-600 px-6 py-2 text-sm font-semibold text-white hover:bg-red-700"
                                >
                                    Submit Observation
                                </button>
                            </form>
                        </div>
                    )}

                    {observations.list.length > 0 && (
                        <div className="space-y-4">
                            {observations.list.map((obs) => {
                                const statusColors: Record<string, string> = {
                                    open: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    in_progress:
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    resolved:
                                        'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                    closed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    rejected:
                                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                };
                                const statusLabels: Record<string, string> = {
                                    open: 'Open',
                                    in_progress: 'In Progress',
                                    resolved: 'Resolved',
                                    closed: 'Closed',
                                    rejected: 'Rejected',
                                };

                                return (
                                    <div
                                        key={obs.id}
                                        className="rounded-md border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                                    >
                                        <div className="mb-2 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${statusColors[obs.status] ?? ''}`}
                                                >
                                                    {statusLabels[obs.status] ??
                                                        obs.status}
                                                </span>
                                                <span className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    by {obs.created_by?.name}
                                                </span>
                                            </div>
                                            <span className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                {formatDateTime(obs.created_at)}
                                            </span>
                                        </div>
                                        <p className="text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                            {obs.description}
                                        </p>
                                        {obs.attachments.length > 0 && (
                                            <div className="mt-2 flex flex-wrap gap-2">
                                                {obs.attachments.map((att) => (
                                                    <span
                                                        key={att.id}
                                                        className="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                                                    >
                                                        📎 {att.file_name}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                        {obs.resolution_note && (
                                            <div className="mt-3 rounded-md bg-purple-50 p-3 dark:bg-purple-900/20">
                                                <p className="text-xs font-semibold text-purple-700 dark:text-purple-400">
                                                    Resolution:
                                                </p>
                                                <p className="text-sm text-purple-600 dark:text-purple-300">
                                                    {obs.resolution_note}
                                                </p>
                                            </div>
                                        )}
                                        {obs.rejection_reason && (
                                            <div className="mt-3 rounded-md bg-red-50 p-3 dark:bg-red-900/20">
                                                <p className="text-xs font-semibold text-red-700 dark:text-red-400">
                                                    Rejection Reason:
                                                </p>
                                                <p className="text-sm text-red-600 dark:text-red-300">
                                                    {obs.rejection_reason}
                                                </p>
                                            </div>
                                        )}
                                        {obs.can_resolve &&
                                            obs.status !== 'closed' &&
                                            obs.status !== 'rejected' && 
                                            (
                                                <div className="mt-3 border-t border-[#e3e3e0] pt-3 dark:border-[#3E3E3A]">
                                                    <form
                                                        onSubmit={(e) => {
                                                            e.preventDefault();

                                                            if (
                                                                !resolveObservationForm.data.resolution_note.trim()
                                                            ) {
                                                                alert(
                                                                    'Please provide a resolution note.',
                                                                );

                                                                return;
                                                            }

                                                            router.post(
                                                                `/observations/${obs.id}/resolve`,
                                                                resolveObservationForm.data,
                                                                {
                                                                    onSuccess:
                                                                        () => {
                                                                            resolveObservationForm.reset(
                                                                                'resolution_note',
                                                                            );
                                                                        },
                                                                },
                                                            );
                                                        }}
                                                        className="space-y-2"
                                                    >
                                                        <input
                                                            type="text"
                                                            value={
                                                                resolveObservationForm
                                                                    .data
                                                                    .resolution_note
                                                            }
                                                            onChange={(e) =>
                                                                resolveObservationForm.setData(
                                                                    'resolution_note',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            placeholder="Resolution note..."
                                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                                        />
                                                        <button
                                                            type="submit"
                                                            className="rounded-md bg-purple-600 px-4 py-1 text-xs font-semibold text-white hover:bg-purple-700"
                                                        >
                                                            Mark as Resolved
                                                        </button>
                                                    </form>
                                                </div>
                                            )}
                                        {obs.can_review &&
                                            obs.status === 'resolved' && (
                                                <div className="mt-3 space-y-2 border-t border-[#e3e3e0] pt-3 dark:border-[#3E3E3A]">
                                                    <label className="block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                                        Review note (optional
                                                        for accept, required
                                                        for reject)
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={
                                                            resolutionReviewByObsId[
                                                                obs.id
                                                            ] ?? ''
                                                        }
                                                        onChange={(e) => {
                                                            setResolutionReviewErrorByObsId(
                                                                (prev) => {
                                                                    const next =
                                                                        {
                                                                            ...prev,
                                                                        };
                                                                    delete next[
                                                                        obs.id
                                                                    ];

                                                                    return next;
                                                                },
                                                            );
                                                            setResolutionReviewByObsId(
                                                                (prev) => ({
                                                                    ...prev,
                                                                    [obs.id]:
                                                                        e
                                                                            .target
                                                                            .value,
                                                                }),
                                                            );
                                                        }}
                                                        placeholder="Acceptance note or rejection reason..."
                                                        className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                                    />
                                                    {resolutionReviewErrorByObsId[
                                                        obs.id
                                                    ] && (
                                                        <p className="text-xs text-red-600 dark:text-red-400">
                                                            {
                                                                resolutionReviewErrorByObsId[
                                                                    obs.id
                                                                ]
                                                            }
                                                        </p>
                                                    )}
                                                    <div className="flex flex-wrap gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                const note =
                                                                    (
                                                                        resolutionReviewByObsId[
                                                                            obs.id
                                                                        ] ?? ''
                                                                    ).trim();
                                                                router.post(
                                                                    `/observations/${obs.id}/accept-resolution`,
                                                                    {
                                                                        acceptance_note:
                                                                            note,
                                                                    },
                                                                );
                                                            }}
                                                            className="rounded-md bg-green-600 px-4 py-1 text-xs font-semibold text-white hover:bg-green-700"
                                                        >
                                                            Accept Resolution
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                const reason =
                                                                    (
                                                                        resolutionReviewByObsId[
                                                                            obs.id
                                                                        ] ?? ''
                                                                    ).trim();

                                                                if (!reason) {
                                                                    setResolutionReviewErrorByObsId(
                                                                        (
                                                                            prev,
                                                                        ) => ({
                                                                            ...prev,
                                                                            [obs.id]:
                                                                                'Please provide a rejection reason.',
                                                                        }),
                                                                    );

                                                                    return;
                                                                }

                                                                setResolutionReviewErrorByObsId(
                                                                    (prev) => {
                                                                        const next =
                                                                            {
                                                                                ...prev,
                                                                            };
                                                                        delete next[
                                                                            obs.id
                                                                        ];

                                                                        return next;
                                                                    },
                                                                );
                                                                router.post(
                                                                    `/observations/${obs.id}/reject-resolution`,
                                                                    {
                                                                        rejection_reason:
                                                                            reason,
                                                                    },
                                                                );
                                                            }}
                                                            className="rounded-md bg-red-600 px-4 py-1 text-xs font-semibold text-white hover:bg-red-700"
                                                        >
                                                            Reject Resolution
                                                        </button>
                                                    </div>
                                                </div>
                                            )}
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {(observations.can_suspend || observations.can_terminate) &&
                        permit.status !== 'terminated' && (
                            <div className="mt-6 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                                <h3 className="mb-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                                    Safety Actions
                                </h3>
                                {observations.can_suspend &&
                                    !observations.is_suspended && (
                                        <div className="mb-4">
                                            <form
                                                onSubmit={(e) => {
                                                    e.preventDefault();

                                                    if (
                                                        !suspensionForm.data.suspension_reason.trim()
                                                    ) {
                                                        alert(
                                                            'Please provide a suspension reason.',
                                                        );

                                                        return;
                                                    }

                                                    if (
                                                        confirm(
                                                            'Are you sure you want to suspend this permit?',
                                                        )
                                                    ) {
                                                        router.post(
                                                            `/permits/${permit.id}/suspend`,
                                                            suspensionForm.data,
                                                            {
                                                                onSuccess:
                                                                    () => {
                                                                        suspensionForm.reset(
                                                                            'suspension_reason',
                                                                        );
                                                                    },
                                                            },
                                                        );
                                                    }
                                                }}
                                                className="space-y-2"
                                            >
                                                <input
                                                    type="text"
                                                    value={
                                                        suspensionForm.data
                                                            .suspension_reason
                                                    }
                                                    onChange={(e) =>
                                                        suspensionForm.setData(
                                                            'suspension_reason',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Suspension reason..."
                                                    className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                                />
                                                <button
                                                    type="submit"
                                                    className="rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                                                >
                                                    Suspend Permit
                                                </button>
                                            </form>
                                        </div>
                                    )}


                                {observations.can_terminate &&
                                    !observations.is_terminated && (
                                        <div>
                                            <form
                                                onSubmit={(e) => {
                                                    e.preventDefault();

                                                    if (
                                                        !terminationForm.data.termination_reason.trim()
                                                    ) {
                                                        alert(
                                                            'Please provide a termination reason.',
                                                        );

                                                        return;
                                                    }

                                                    if (
                                                        confirm(
                                                            'Are you sure you want to permanently terminate this permit? This action cannot be undone.',
                                                        )
                                                    ) {
                                                        router.post(
                                                            `/permits/${permit.id}/terminate`,
                                                            terminationForm.data,
                                                            {
                                                                onSuccess:
                                                                    () => {
                                                                        terminationForm.reset(
                                                                            'termination_reason',
                                                                        );
                                                                    },
                                                            },
                                                        );
                                                    }
                                                }}
                                                className="space-y-2"
                                            >
                                                <input
                                                    type="text"
                                                    value={
                                                        terminationForm.data
                                                            .termination_reason
                                                    }
                                                    onChange={(e) =>
                                                        terminationForm.setData(
                                                            'termination_reason',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Termination reason..."
                                                    className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                                />
                                                <button
                                                    type="submit"
                                                    className="rounded-md bg-red-800 px-4 py-2 text-sm font-semibold text-white hover:bg-red-900"
                                                >
                                                    Terminate Permit
                                                </button>
                                            </form>
                                        </div>
                                    )}
                            </div>
                        )}
                </div>
            </div>
        </AppLayout>
    );
}
