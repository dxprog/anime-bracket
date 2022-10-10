import React, { useContext } from 'react';

const ANON_USER_ID = 0;

const AuthContext = React.createContext();
export const AuthContextProvider = AuthContext.Provider;
export const useAuth = () => {
  const context = useContext(AuthContext);
  const userId = context?.userId ? parseInt(context.userId, 10) : ANON_USER_ID;

  return {
    isAuthenticated: userId > ANON_USER_ID,
    userId,
    csrfToken: context?.csrfToken,
  };
};
