import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface YellowForm {
    id: number;
    student_id: string;
    student_name: {
        first_name: string;
        middle_name: string | null;
        last_name: string;
    };
    academic_info: {
        course: string;
        department: string;
        year: string;
    };
    violation: {
        name: string;
        legend: string;
        description: string;
        other_violation: string | null;
    };
    dates: {
        created_at: string;
        date: string;
        compliance_date: string | null;
    };
    status: {
        complied: boolean;
        dean_verification: boolean;
        head_approval: boolean;
        verification_notes: string | null;
    };
    suspension: {
        is_suspended: boolean;
        suspension_status: string;
        suspension_start_date: string | null;
        suspension_end_date: string | null;
        suspension_notes: string | null;
        remaining_days: number | null;
    };
    faculty: {
        name: string;
        signature: string;
    };
}

export default function YellowFormSearch() {
    const [studentId, setStudentId] = useState('');
    const [searchResults, setSearchResults] = useState<YellowForm[] | null>(null);
    const [loading, setLoading] = useState(false);

    const handleSearch = async () => {
        setLoading(true);
        try {
            const response = await fetch(`/api/yellow-forms/search?student_id=${studentId}`);
            const data = await response.json();
            setSearchResults(data);
        } catch (error) {
            console.error('Error searching yellow forms:', error);
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (date: string | null) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString();
    };

    return (
        <div className="min-h-screen bg-white font-mono">
            <div className="container mx-auto py-8 px-4 max-w-5xl">
                <Card className="bg-[#FFD900] border-4 border-[#101511] shadow-[8px_8px_0px_0px_rgba(16,21,17,1)]">
                    <CardHeader className="border-b-4 border-[#101511] px-6 py-4 bg-[#648E37] bg-opacity-10">
                        <div className="flex flex-col items-center mb-4">
                            <img src="images/logo.png" alt="SPUP Logo" className="w-24 h-24 mb-2" />
                            <CardTitle className="text-xl text-[#101511] font-bold tracking-tight">[ SPUP-OSA YELLOW FORM ]</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent className="p-6 bg-[#FFD900]">
                        <div className="flex gap-4 max-w-lg">
                            <Input
                                type="text"
                                placeholder="Enter Student ID"
                                value={studentId}
                                onChange={(e) => setStudentId(e.target.value)}
                                className="flex-1 border-2 border-[#101511] px-4 py-2 font-mono focus:ring-0 focus:border-[#648E37] text-[#101511] placeholder:text-gray-500"
                            />
                            <Button
                                onClick={handleSearch}
                                disabled={loading}
                                className="bg-[#648E37] hover:bg-[#101511] text-white px-6 border-2 border-[#101511] shadow-[4px_4px_0px_0px_rgba(16,21,17,1)] hover:shadow-[2px_2px_0px_0px_rgba(16,21,17,1)] hover:translate-x-[2px] hover:translate-y-[2px] transition-all"
                            >
                                {loading ? '[ ... ]' : '[ SEARCH ]'}
                            </Button>
                        </div>

                        {searchResults && (
                            <div className="mt-8">
                                {searchResults.length > 0 ? (
                                    <div className="space-y-6">
                                        {searchResults.map((form: YellowForm) => (
                                            <Card key={form.id} className="border-2 border-[#101511] bg-[#FFD900] shadow-[4px_4px_0px_0px_rgba(16,21,17,1)]">
                                                <CardContent className="p-6">
                                                    {/* Header Section */}
                                                    <div className="flex justify-between items-start border-b-2 border-[#101511] pb-4">
                                                        <div>
                                                            <h3 className="text-lg font-bold mb-1 text-[#101511] tracking-tight">[FORM #{form.id}]</h3>
                                                            <p className="text-sm text-[#101511]">
                                                                {form.student_name.first_name} {form.student_name.middle_name} {form.student_name.last_name}
                                                            </p>
                                                            <p className="text-sm text-[#101511]">ID: {form.student_id}</p>
                                                        </div>
                                                        <div className="px-3 py-1 border-2 border-[#101511] text-sm text-[#101511] bg-[#648E37] bg-opacity-10">
                                                            {form.status.complied ? '[ COMPLIED ]' : '[ PENDING ]'}
                                                        </div>
                                                    </div>

                                                    <div className="grid gap-6 mt-6">
                                                        {/* Academic Info */}
                                                        <div className="border-2 border-[#101511] p-4 bg-[#FFD900]">
                                                            <h4 className="font-bold mb-3 text-sm uppercase tracking-wide text-[#101511] bg-[#648E37] bg-opacity-10 p-2 border-b-2 border-[#101511]">[ ACADEMIC INFORMATION ]</h4>
                                                            <div className="grid grid-cols-3 gap-4">
                                                                <div>
                                                                    <p className="text-xs mb-1 text-[#101511] font-bold">COURSE:</p>
                                                                    <p className="text-sm text-[#101511]">{form.academic_info.course}</p>
                                                                </div>
                                                                <div>
                                                                    <p className="text-xs mb-1 text-[#101511] font-bold">DEPARTMENT:</p>
                                                                    <p className="text-sm text-[#101511]">{form.academic_info.department}</p>
                                                                </div>
                                                                <div>
                                                                    <p className="text-xs mb-1 text-[#101511] font-bold">YEAR:</p>
                                                                    <p className="text-sm text-[#101511]">{form.academic_info.year}</p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {/* Violation Details */}
                                                        <div className="border-2 border-[#101511] p-4 bg-[#FFD900]">
                                                            <h4 className="font-bold mb-3 text-sm uppercase tracking-wide text-[#101511] bg-[#648E37] bg-opacity-10 p-2 border-b-2 border-[#101511]">[ VIOLATION DETAILS ]</h4>
                                                            <div className="space-y-3">
                                                                <div>
                                                                    <p className="text-xs mb-1 text-[#101511] font-bold">VIOLATION:</p>
                                                                    <p className="text-sm font-medium text-[#101511]">{form.violation.name}</p>
                                                                </div>
                                                                {form.violation.other_violation && (
                                                                    <div>
                                                                        <p className="text-xs mb-1 text-[#101511] font-bold">ADDITIONAL NOTES:</p>
                                                                        <p className="text-sm text-[#101511]">{form.violation.other_violation}</p>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>

                                                        {/* Suspension Status */}
                                                        {form.suspension.is_suspended && (
                                                            <div className="border-2 border-[#101511] p-4 bg-[#FFD900]">
                                                                <h4 className="font-bold mb-3 text-sm uppercase tracking-wide text-[#101511] bg-[#648E37] bg-opacity-10 p-2 border-b-2 border-[#101511]">[ SUSPENSION STATUS ]</h4>
                                                                <div className="space-y-3">
                                                                    <div>
                                                                        <p className="text-xs mb-1 text-[#101511] font-bold">STATUS:</p>
                                                                        <p className="text-sm text-[#101511]">{form.suspension.suspension_status}</p>
                                                                    </div>
                                                                    {form.suspension.remaining_days !== null && (
                                                                        <div>
                                                                            <p className="text-xs mb-1 text-[#101511] font-bold">REMAINING DAYS:</p>
                                                                            <p className="text-sm text-[#101511]">{form.suspension.remaining_days}</p>
                                                                        </div>
                                                                    )}
                                                                    {form.suspension.suspension_notes && (
                                                                        <div>
                                                                            <p className="text-xs mb-1 text-[#101511] font-bold">NOTES:</p>
                                                                            <p className="text-sm text-[#101511]">{form.suspension.suspension_notes}</p>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        )}

                                                        {/* Verification & Dates */}
                                                        <div className="grid grid-cols-2 gap-6">
                                                            <div className="border-2 border-[#101511] p-4 bg-[#FFD900]">
                                                                <h4 className="font-bold mb-3 text-sm uppercase tracking-wide text-[#101511] bg-[#648E37] bg-opacity-10 p-2 border-b-2 border-[#101511]">[ VERIFICATION STATUS ]</h4>
                                                                <div className="space-y-3">
                                                                    <div>
                                                                        <p className="text-xs mb-1 text-[#101511] font-bold">DEAN VERIFICATION:</p>
                                                                        <p className="text-sm text-[#101511]">{form.status.dean_verification ? '✓ VERIFIED' : '✗ PENDING'}</p>
                                                                    </div>
                                                                    <div>
                                                                        <p className="text-xs mb-1 text-[#101511] font-bold">HEAD APPROVAL:</p>
                                                                        <p className="text-sm text-[#101511]">{form.status.head_approval ? '✓ APPROVED' : '✗ PENDING'}</p>
                                                                    </div>
                                                                    {form.status.verification_notes && (
                                                                        <div>
                                                                            <p className="text-xs mb-1 text-[#101511] font-bold">NOTES:</p>
                                                                            <p className="text-sm text-[#101511]">{form.status.verification_notes}</p>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                            <div className="border-2 border-[#101511] p-4 bg-[#FFD900]">
                                                                <h4 className="font-bold mb-3 text-sm uppercase tracking-wide text-[#101511] bg-[#648E37] bg-opacity-10 p-2 border-b-2 border-[#101511]">[ IMPORTANT DATES ]</h4>
                                                                <div className="space-y-3">
                                                                    <div>
                                                                        <p className="text-xs mb-1 text-[#101511] font-bold">ISSUE DATE:</p>
                                                                        <p className="text-sm text-[#101511]">{formatDate(form.dates.date)}</p>
                                                                    </div>
                                                                    <div>
                                                                        <p className="text-xs mb-1 text-[#101511] font-bold">COMPLIANCE DATE:</p>
                                                                        <p className="text-sm text-[#101511]">{formatDate(form.dates.compliance_date)}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {/* Faculty Information */}
                                                        <div className="border-t-2 border-[#101511] pt-4">
                                                            <div className="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <p className="text-xs mb-1 text-[#101511] font-bold">FACULTY/PSG:</p>
                                                                    <p className="text-sm text-[#101511]">{form.faculty.name}</p>
                                                                </div>
                                                                <div>
                                                                    <p className="text-xs mb-1 text-[#101511] font-bold">NOTED BY:</p>
                                                                    <p className="text-sm text-[#101511]">{form.faculty.signature}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-center py-8 text-[#101511] font-mono border-2 border-[#101511] bg-[#648E37] bg-opacity-10 mt-4">[ NO YELLOW FORMS FOUND ]</p>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
