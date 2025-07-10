import { ProjectRoleManagerFixed as ProjectRoleManager } from '@/components/permissions/ProjectRoleManagerFixed';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { useFeature } from '@/composables/useFeature';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { Briefcase, DollarSign, Eye, MoreVertical, Plus, Settings, Shield, UserMinus, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Member {
    id: number;
    name: string;
    email: string;
    role: 'member' | 'manager' | 'viewer';
    custom_rate?: number;
}

interface MembersData {
    members: Member[];
    available_users: any[];
    total_members: number;
}

interface ProjectMembersPanelProps {
    projectId: number;
    projectName?: string;
    canManage: boolean;
    onAssignMembers?: () => void;
}

export default function ProjectMembersPanel({ projectId, projectName = 'Project', canManage, onAssignMembers }: ProjectMembersPanelProps) {
    const hasPermissionFeature = useFeature('project_permissions');
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<MembersData | null>(null);
    const [updatingRole, setUpdatingRole] = useState<number | null>(null);
    const [selectedMember, setSelectedMember] = useState<Member | null>(null);
    const [showPermissions, setShowPermissions] = useState(false);

    useEffect(() => {
        fetchProjectMembers();
    }, [projectId]);

    const fetchProjectMembers = async () => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/projects/${projectId}/members`);
            setData(response.data.data);
        } catch (error) {
            toast.error('Failed to load project members');
        } finally {
            setLoading(false);
        }
    };

    const updateMemberRole = async (userId: number, newRole: string) => {
        try {
            setUpdatingRole(userId);
            await axios.put(`/api/projects/${projectId}/members/${userId}`, {
                role: newRole,
            });
            toast.success('Member role updated successfully');
            fetchProjectMembers();
        } catch (error) {
            toast.error('Failed to update member role');
        } finally {
            setUpdatingRole(null);
        }
    };

    const removeMember = async (userId: number, userName: string) => {
        if (!confirm(`Are you sure you want to remove ${userName} from this project?`)) {
            return;
        }

        try {
            await axios.delete(`/api/projects/${projectId}/members/${userId}`);
            toast.success('Member removed successfully');
            fetchProjectMembers();
        } catch (error) {
            toast.error('Failed to remove member');
        }
    };

    const openPermissions = (member: Member) => {
        setSelectedMember(member);
        setShowPermissions(true);
    };

    const closePermissions = () => {
        setShowPermissions(false);
        // Clear selected member after modal animation
        setTimeout(() => {
            setSelectedMember(null);
        }, 300);
    };

    const handlePermissionsSaved = () => {
        toast.success('Permisos actualizados correctamente');
        closePermissions();
        // Refresh the member list after a delay
        setTimeout(() => {
            fetchProjectMembers();
        }, 800);
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
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

    const getRoleBadgeVariant = (role: string) => {
        switch (role) {
            case 'manager':
                return 'default';
            case 'viewer':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    if (loading) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Team Members</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-3">
                        <Skeleton className="h-16 w-full" />
                        <Skeleton className="h-16 w-full" />
                        <Skeleton className="h-16 w-full" />
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>Team Members</CardTitle>
                    <CardDescription>{data?.total_members || 0} members assigned to this project</CardDescription>
                </div>
                {canManage && (
                    <Button onClick={onAssignMembers} size="sm">
                        <Plus className="mr-2 h-4 w-4" />
                        Add Members
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                {data?.members && data.members.length > 0 ? (
                    <div className="space-y-3">
                        {data.members.map((member) => (
                            <div
                                key={member.id}
                                className={cn('flex items-center justify-between rounded-lg border p-4', 'hover:bg-muted/50 transition-colors')}
                            >
                                <div className="flex items-center gap-3">
                                    <Avatar>
                                        <AvatarFallback>{getInitials(member.name)}</AvatarFallback>
                                    </Avatar>
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <h4 className="font-medium">{member.name}</h4>
                                            <Badge variant={getRoleBadgeVariant(member.role)}>
                                                <span className="flex items-center gap-1">
                                                    {getRoleIcon(member.role)}
                                                    {member.role}
                                                </span>
                                            </Badge>
                                            {hasPermissionFeature && (
                                                <Badge variant="outline" className="gap-1">
                                                    <Settings className="h-3 w-3" />
                                                    Permisos
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-muted-foreground text-sm">{member.email}</p>
                                        {member.custom_rate && (
                                            <div className="text-muted-foreground mt-1 flex items-center gap-1 text-sm">
                                                <DollarSign className="h-3 w-3" />${member.custom_rate}/hour
                                            </div>
                                        )}
                                    </div>
                                </div>
                                {canManage && (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm" disabled={updatingRole === member.id}>
                                                <MoreVertical className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            {hasPermissionFeature && (
                                                <>
                                                    <DropdownMenuItem onClick={() => openPermissions(member)}>
                                                        <Shield className="mr-2 h-4 w-4" />
                                                        Manage Permissions
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                </>
                                            )}
                                            <DropdownMenuItem disabled className="text-muted-foreground text-xs">
                                                Change Role
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                onClick={() => updateMemberRole(member.id, 'member')}
                                                disabled={member.role === 'member'}
                                            >
                                                <Briefcase className="mr-2 h-4 w-4" />
                                                Set as Member
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onClick={() => updateMemberRole(member.id, 'manager')}
                                                disabled={member.role === 'manager'}
                                            >
                                                <Users className="mr-2 h-4 w-4" />
                                                Set as Manager
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onClick={() => updateMemberRole(member.id, 'viewer')}
                                                disabled={member.role === 'viewer'}
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                Set as Viewer
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => removeMember(member.id, member.name)} className="text-destructive">
                                                <UserMinus className="mr-2 h-4 w-4" />
                                                Remove from Project
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                )}
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-muted-foreground py-8 text-center">
                        <Users className="text-muted-foreground/50 mx-auto mb-3 h-12 w-12" />
                        <p>No team members assigned</p>
                        {canManage && (
                            <Button onClick={onAssignMembers} variant="outline" size="sm" className="mt-3">
                                <Plus className="mr-2 h-4 w-4" />
                                Add First Member
                            </Button>
                        )}
                    </div>
                )}
            </CardContent>

            {/* Permission Manager Modal */}
            {hasPermissionFeature && selectedMember && (
                <ProjectRoleManager
                    open={showPermissions}
                    onOpenChange={closePermissions}
                    projectId={projectId}
                    projectName={projectName}
                    userId={selectedMember.id}
                    userName={selectedMember.name}
                    userEmail={selectedMember.email}
                    onSave={handlePermissionsSaved}
                />
            )}
        </Card>
    );
}
