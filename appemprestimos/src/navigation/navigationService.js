import {CommonActions, createNavigationContainerRef} from '@react-navigation/native';

import {AuthNav, StackNav} from './navigationKeys';

export const navigationRef = createNavigationContainerRef();

export function resetToAuth(authScreen = AuthNav.SignIn) {
  if (!navigationRef.isReady()) return;

  navigationRef.dispatch(
    CommonActions.reset({
      index: 0,
      routes: [
        {
          name: StackNav.AuthNavigation,
          state: {
            index: 0,
            routes: [{name: authScreen}],
          },
        },
      ],
    }),
  );
}

export function resetToLogin() {
  return resetToAuth(AuthNav.SignIn);
}

