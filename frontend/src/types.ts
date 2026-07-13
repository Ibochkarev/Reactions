export interface WidgetConfig {
  api: string;
  classKey: string;
  objectId: number;
  set: string;
  context: string;
  csrf: string;
}

export interface CountsData {
  class_key: string;
  object_id: number;
  context: string;
  counts: Record<string, number>;
  total: number;
  user_reaction: string[];
}

export interface ReactionData {
  action: 'added' | 'removed' | 'changed';
  counts: Record<string, number>;
  total: number;
  user_reaction: string[];
  type: string;
}

export interface ApiSuccess<T> {
  success: true;
  data?: T;
  csrf?: string;
}

export interface ApiError {
  success: false;
  error: string;
  code: string;
}

export type ApiResponse<T> = ApiSuccess<T> | ApiError;

export interface ReactionTypeDef {
  name: string;
  label: string;
}

export const REACTION_SETS: Record<string, ReactionTypeDef[]> = {
  updown: [
    { name: 'like', label: '👍' },
    { name: 'dislike', label: '👎' },
  ],
  github: [
    { name: 'like', label: '👍' },
    { name: 'dislike', label: '👎' },
    { name: 'love', label: '❤️' },
    { name: 'funny', label: '😂' },
    { name: 'wow', label: '😮' },
    { name: 'sad', label: '😢' },
    { name: 'angry', label: '😡' },
    { name: 'hooray', label: '🎉' },
  ],
};
