import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Skeleton } from '@/components/ui/skeleton';
import axios from 'axios';
import { AlertCircle, Briefcase, DollarSign, Eye, UserPlus, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import UserSelector from './UserSelector';

interface User {
    id: number;
    name: string;
    email: string;
    role?: string;
}

interface AssignmentData {
    selectedUsers: number[];
    role: 'member' | 'manager' | 'viewer';
    customRate?: number;
}

interface AssignMembersModalProps {
    open: boolean;
    onClose: () => void;
    projectId: number;
    projectName: string;
    onSuccess: () => void;
}

export default function AssignMembersModal({ open, onClose, projectId, projectName, onSuccess }: AssignMembersModalProps) {
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [availableUsers, setAvailableUsers] = useState<User[]>([]);
    const [assignmentData, setAssignmentData] = useState<AssignmentData>({
        selectedUsers: [],
        role: 'member',
        customRate: undefined,
    });

    useEffect(() => {
        if (open) {
            fetchAvailableUsers();
        }
    }, [open, projectId]);

    const fetchAvailableUsers = async () => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/projects/${projectId}/members/available`);
            setAvailableUsers(response.data.data);
        } catch (error) {
            console.error('Error fetching available users:', error);
            toast.error('Failed to load available users');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async () => {
        if (assignmentData.selectedUsers.length === 0) {
            toast.error('Please select at least one user');
            return;
        }

        try {
            setSubmitting(true);

            await axios.post(`/api/projects/${projectId}/members`, {
                user_ids: assignmentData.selectedUsers,
                role: assignmentData.role,
                custom_rate: assignmentData.customRate || null,
            });

            toast.success('Members assigned successfully');
            onSuccess();
            onClose();
            resetForm();
        } catch (error: any) {
            console.error('Error assigning members:', error);
            toast.error(error.response?.data?.message || 'Failed to assign members');
        } finally {
            setSubmitting(false);
        }
    };

    const resetForm = () => {
        setAssignmentData({
            selectedUsers: [],
            role: 'member',
            customRate: undefined,
        });
    };

    const getRoleIcon = (role: string) => {
        switch (role) {
            case 'manager':
                return <Users className="h-4 w-4" />;
            case 'viewer':
                return <Eye className="h-4 w-4" />;
            default:
                return <Briefcase className="h-4 w-4" />;
        }
    };

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Add Members to {projectName}</DialogTitle>
                    <DialogDescription>Select team members to add to this project and define their role.</DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4">
                    <div className="space-y-4">
                        <div>
                            <Label className="mb-3 flex items-center gap-2 text-base font-semibold">
                                <UserPlus className="h-4 w-4" />
                                Select Team Members
                            </Label>
                            {loading ? (
                                <div className="space-y-2">
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                </div>
                            ) : availableUsers.length > 0 ? (
                                <UserSelector
                                    users={availableUsers}
                                    selectedUsers={assignmentData.selectedUsers}
                                    onSelectionChange={(selected) => setAssignmentData({ ...assignmentData, selectedUsers: selected })}
                                />
                            ) : (
                                <Alert>
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        No users available to assign. All team members may already be assigned to this project.
                                    </AlertDescription>
                                </Alert>
                            )}
                        </div>

                        <div>
                            <Label className="mb-3 text-base font-semibold">Project Role</Label>
                            <RadioGroup
                                value={assignmentData.role}
                                onValueChange={(value) => setAssignmentData({ ...assignmentData, role: value as any })}
                                className="mt-2"
                            >
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="member" id="member" />
                                    <Label htmlFor="member" className="flex cursor-pointer items-center gap-2">
                                        <Briefcase className="h-4 w-4" />
                                        Member
                                        <span className="text-muted-foreground ml-2 text-sm">Can track time and manage own entries</span>
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="manager" id="manager" />
                                    <Label htmlFor="manager" className="flex cursor-pointer items-center gap-2">
                                        <Users className="h-4 w-4" />
                                        Manager
                                        <span className="text-muted-foreground ml-2 text-sm">Can manage project and view all data</span>
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="viewer" id="viewer" />
                                    <Label htmlFor="viewer" className="flex cursor-pointer items-center gap-2">
                                        <Eye className="h-4 w-4" />
                                        Viewer
                                        <span className="text-muted-foreground ml-2 text-sm">Can only view project data</span>
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>

                        <div>
                            <Label htmlFor="customRate" className="flex items-center gap-2">
                                <DollarSign className="h-4 w-4" />
                                Custom Hourly Rate (Optional)
                            </Label>
                            <Input
                                id="customRate"
                                type="number"
                                placeholder="Leave empty to use each user's default rate"
                                value={assignmentData.customRate || ''}
                                onChange={(e) =>
                                    setAssignmentData({
                                        ...assignmentData,
                                        customRate: e.target.value ? parseFloat(e.target.value) : undefined,
                                    })
                                }
                                className="mt-2"
                                min="0"
                                step="0.01"
                            />
                            <p className="text-muted-foreground mt-1 text-sm">Override the default billing rate for selected users on this project</p>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={submitting}>
                        Cancel
                    </Button>
                    <Button onClick={handleSubmit} disabled={submitting || loading || assignmentData.selectedUsers.length === 0}>
                        {submitting ? 'Adding Members...' : 'Add Members'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
