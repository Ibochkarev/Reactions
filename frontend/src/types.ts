export interface WidgetConfig {
  api: string;
  classKey: string;
  objectId: number;
  set: string;
  context: string;
  csrf: string;
  /** undefined = no data-types attr; [] = empty filter (show no buttons) */
  types?: string[];
  /** Mirrors ReactionSet.exclusive when provided via data-exclusive */
  exclusive: boolean;
  /** Mirrors reactions_allow_multiple / data-allow-multiple */
  allowMultiple: boolean;
}

export interface ReactionsGlobalConfig {
  api?: string;
}

declare global {
  interface Window {
    Reactions?: {
      init: (root?: ParentNode) => unknown[];
      config?: ReactionsGlobalConfig;
    };
  }
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

const UPDOWN: ReactionTypeDef[] = [
  { name: 'like', label: '👍' },
  { name: 'dislike', label: '👎' },
];

const GITHUB: ReactionTypeDef[] = [
  ...UPDOWN,
  { name: 'love', label: '❤️' },
  { name: 'funny', label: '😂' },
  { name: 'wow', label: '😮' },
  { name: 'sad', label: '😢' },
  { name: 'angry', label: '😡' },
  { name: 'hooray', label: '🎉' },
];

const FULL_EXTRA: ReactionTypeDef[] = [
  { name: 'rocket', label: '🚀' },
  { name: 'eyes', label: '👀' },
  { name: 'fire', label: '🔥' },
  { name: 'clap', label: '👏' },
  { name: 'thinking', label: '🤔' },
  { name: 'party', label: '🥳' },
  { name: 'star', label: '⭐' },
  { name: 'beer', label: '🍺' },
  { name: 'sparkles', label: '✨' },
  { name: 'hundred', label: '💯' },
  { name: 'pray', label: '🙏' },
  { name: 'muscle', label: '💪' },
  { name: 'cool', label: '😎' },
  { name: 'heart_eyes', label: '😍' },
  { name: 'confused', label: '😕' },
  { name: 'raised_hands', label: '🙌' },
];

export const REACTION_SETS: Record<string, ReactionTypeDef[]> = {
  updown: UPDOWN,
  github: GITHUB,
  full: [...GITHUB, ...FULL_EXTRA],
};
