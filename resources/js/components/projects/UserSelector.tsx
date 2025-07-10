import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { Search, User } from 'lucide-react';
import { useMemo, useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    role?: string;
}

interface UserSelectorProps {
    users: User[];
    selectedUsers: number[];
    onSelectionChange: (selectedIds: number[]) => void;
    maxHeight?: string;
}

export default function UserSelector({ users, selectedUsers, onSelectionChange, maxHeight = '400px' }: UserSelectorProps) {
    const [searchTerm, setSearchTerm] = useState('');

    const filteredUsers = useMemo(() => {
        return users.filter((user) => {
            const searchLower = searchTerm.toLowerCase();
            return user.name.toLowerCase().includes(searchLower) || user.email.toLowerCase().includes(searchLower);
        });
    }, [users, searchTerm]);

    const handleToggleUser = (userId: number) => {
        if (selectedUsers.includes(userId)) {
            onSelectionChange(selectedUsers.filter((id) => id !== userId));
        } else {
            onSelectionChange([...selectedUsers, userId]);
        }
    };

    const handleSelectAll = () => {
        if (selectedUsers.length === filteredUsers.length) {
            onSelectionChange([]);
        } else {
            onSelectionChange(filteredUsers.map((u) => u.id));
        }
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getRoleBadgeVariant = (role?: string) => {
        switch (role) {
            case 'owner':
                return 'default';
            case 'admin':
                return 'destructive';
            case 'manager':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    return (
        <div className="space-y-4">
            <div className="relative">
                <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform" />
                <Input
                    placeholder="Search users by name or email..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10"
                />
            </div>

            <div className="rounded-lg border">
                <div className="bg-muted/50 flex items-center justify-between border-b p-3">
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            checked={filteredUsers.length > 0 && selectedUsers.length === filteredUsers.length}
                            onCheckedChange={handleSelectAll}
                        />
                        <span className="text-sm font-medium">
                            Select All ({selectedUsers.length} of {filteredUsers.length})
                        </span>
                    </div>
                </div>

                <ScrollArea className={`h-[${maxHeight}]`}>
                    {filteredUsers.length > 0 ? (
                        <div className="divide-y">
                            {filteredUsers.map((user) => (
                                <div
                                    key={user.id}
                                    className={cn(
                                        'hover:bg-muted/50 flex cursor-pointer items-center space-x-3 p-3 transition-colors',
                                        selectedUsers.includes(user.id) && 'bg-muted/50',
                                    )}
                                    onClick={() => handleToggleUser(user.id)}
                                >
                                    <Checkbox
                                        checked={selectedUsers.includes(user.id)}
                                        onCheckedChange={() => handleToggleUser(user.id)}
                                        onClick={(e) => e.stopPropagation()}
                                    />
                                    <Avatar className="h-8 w-8">
                                        <AvatarFallback className="text-xs">{getInitials(user.name)}</AvatarFallback>
                                    </Avatar>
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">{user.name}</span>
                                            {user.role && (
                                                <Badge variant={getRoleBadgeVariant(user.role)} className="text-xs">
                                                    {user.role}
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-muted-foreground text-sm">{user.email}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="py-8 text-center">
                            <User className="text-muted-foreground/50 mx-auto mb-3 h-12 w-12" />
                            <p className="text-muted-foreground">{searchTerm ? 'No users match your search' : 'No users available'}</p>
                        </div>
                    )}
                </ScrollArea>
            </div>
        </div>
    );
}
