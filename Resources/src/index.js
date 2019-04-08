import React from 'react'
import styled from 'styled-components'


const Wrapper = styled.div`
  background-color: red;
`
const App = ({test}) => <Wrapper>My first component: {test}</Wrapper>

export default App